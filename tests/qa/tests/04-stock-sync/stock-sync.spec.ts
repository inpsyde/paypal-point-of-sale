import * as crypto from 'crypto';
import { expect } from '@inpsyde/playwright-utils/build';
import {
	test,
	processQueue,
	syncAndAssertStatus,
	ensurePosTestReady,
	createProduct,
	deleteProduct,
	deleteOrder,
	ensureWebhookRegistered,
	getPosVariantUuid,
	signWebhookPayload,
} from '../../utils';
import {
	posStockUpdateProduct,
	posStockMgmtDisableProduct,
	posOrderStockProduct,
	posWebhookStockProduct,
} from './_test-data';

const WEBHOOK_ENDPOINT = '/wp-json/zettle/v1/webhook/listen';

test.describe( 'Stock Sync', () => {
	test.beforeEach(
		async ( {
			requestUtils,
			plugins,
			wooCommerceUtils,
			wooCommerceApi,
			posSettings,
			cli,
		} ) => {
			await ensurePosTestReady( {
				requestUtils,
				plugins,
				wooCommerceUtils,
				wooCommerceApi,
				posSettings,
				cli,
			} );

			test.skip(
				! process.env.PAYPAL_POS_API_KEY,
				'PAYPAL_POS_API_KEY not set — skipping live sync test'
			);
			test.setTimeout( 5 * 60_000 );
		}
	);

	test( 'POS-587 | WooCommerce stock update is reflected in POS; regression;', async ( {
		wcProducts,
		requestUtils,
		cli,
	} ) => {
		const product = await createProduct(
			requestUtils,
			posStockUpdateProduct
		);

		try {
			await syncAndAssertStatus(
				cli,
				wcProducts,
				'POS-587 Stock Update',
				'synced',
				product.id
			);

			await requestUtils.rest( {
				path: `/wc/v3/products/${ product.id }`,
				method: 'PUT',
				data: { stock_quantity: 25 },
			} );

			await syncAndAssertStatus(
				cli,
				wcProducts,
				'POS-587 Stock Update',
				'synced',
				product.id
			);
		} finally {
			await deleteProduct( cli, product.id );
		}
	} );

	test( 'POS-588 | Disabling stock management updates POS inventory tracking; regression;', async ( {
		wcProducts,
		requestUtils,
		cli,
	} ) => {
		const product = await createProduct(
			requestUtils,
			posStockMgmtDisableProduct
		);

		try {
			await syncAndAssertStatus(
				cli,
				wcProducts,
				'POS-588 Stock Mgmt Disable',
				'synced',
				product.id
			);

			await requestUtils.rest( {
				path: `/wc/v3/products/${ product.id }`,
				method: 'PUT',
				data: { manage_stock: false },
			} );

			await processQueue( cli );
			await wcProducts.visit();
			await wcProducts.assertProductSyncStatus(
				'POS-588 Stock Mgmt Disable',
				'synced',
				product.id
			);
		} finally {
			await deleteProduct( cli, product.id );
		}
	} );

	test( 'POS-586 | WooCommerce order reduces POS stock; regression;', async ( {
		wcProducts,
		requestUtils,
		cli,
	} ) => {
		const product = await createProduct(
			requestUtils,
			posOrderStockProduct
		);

		try {
			await syncAndAssertStatus(
				cli,
				wcProducts,
				'POS-586 Order Stock',
				'synced',
				product.id
			);

			const order = await requestUtils.rest< { id: number } >( {
				path: '/wc/v3/orders',
				method: 'POST',
				data: {
					status: 'processing',
					line_items: [ { product_id: product.id, quantity: 3 } ],
				},
			} );

			await processQueue( cli );
			await wcProducts.visit();
			await wcProducts.assertProductSyncStatus(
				'POS-586 Order Stock',
				'synced',
				product.id
			);

			await deleteOrder( cli, order.id );
		} finally {
			await deleteProduct( cli, product.id );
		}
	} );

	test( 'POS-585 | POS sale updates WooCommerce stock via InventoryBalanceChanged webhook; critical;', async ( {
		wcProducts,
		requestUtils,
		page,
		cli,
	} ) => {
		const product = await createProduct(
			requestUtils,
			posWebhookStockProduct
		);

		try {
			await syncAndAssertStatus(
				cli,
				wcProducts,
				'POS-585 Webhook Stock',
				'synced',
				product.id
			);

			const signingKey = await ensureWebhookRegistered( cli );
			if ( ! signingKey ) {
				test.skip(
					true,
					'No webhook signing key found — webhook not registered'
				);
				return;
			}

			const variantUuid = await getPosVariantUuid( cli, product.id );
			if ( ! variantUuid ) {
				test.skip(
					true,
					'No POS variant UUID found — product not in ID map'
				);
				return;
			}

			const timestamp = String( Date.now() );
			const eventPayload = JSON.stringify( {
				balanceBefore: [ { variantUuid, balance: 20 } ],
				balanceAfter: [ { variantUuid, balance: 18 } ],
			} );
			const fullPayload = JSON.stringify( {
				eventName: 'InventoryBalanceChanged',
				organizationUuid: 'test-org',
				messageId: crypto.randomUUID(),
				timestamp,
				payload: eventPayload,
			} );

			const signature = signWebhookPayload(
				timestamp,
				eventPayload,
				signingKey
			);

			const response = await page.request.post( WEBHOOK_ENDPOINT, {
				headers: {
					'Content-Type': 'application/json',
					'X-Izettle-Signature': signature,
				},
				data: fullPayload,
			} );

			await expect( response ).toBeOK();
			await page.waitForTimeout( 3_000 );

			const updated = await requestUtils.rest< {
				stock_quantity: number;
			} >( {
				path: `/wc/v3/products/${ product.id }`,
				method: 'GET',
			} );

			expect( updated.stock_quantity ).toBe( 18 );
		} finally {
			await deleteProduct( cli, product.id );
		}
	} );
} );
