import * as crypto from 'crypto';
import { expect } from '@inpsyde/playwright-utils/build';
import {
	test,
	requireEnv,
	processQueue,
	syncAndAssertStatus,
	ensurePosTestReady,
	createProduct,
	deleteProduct,
	deleteOrder,
	canReceiveWebhooks,
	ensureWebhookRegistered,
	getPosVariantUuid,
	getPosProductUuid,
	getPosStockBalance,
	signWebhookPayload,
	ZettleApiClient,
} from '../../utils';
import {
	posStockUpdateProduct,
	posStockMgmtDisableProduct,
	posOrderStockProduct,
	posWebhookStockProduct,
} from './src';

const WEBHOOK_ENDPOINT = '/wp-json/zettle/v1/webhook/listen';
const ZETTLE_CLIENT_ID = 'de149dc7-44b5-4390-ab64-88e301771f06';

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

			requireEnv( 'PAYPAL_POS_API_KEY' );
			test.setTimeout( 5 * 60_000 );
		}
	);

	test( 'POS-587 | WooCommerce Stock Update Reflected in POS @regression', async ( {
		wcProducts,
		request,
		cli,
		wooCommerceApi,
	} ) => {
		const zettleApi = new ZettleApiClient( request );
		await zettleApi.authenticate(
			ZETTLE_CLIENT_ID,
			requireEnv( 'PAYPAL_POS_API_KEY' )
		);

		const product = await createProduct(
			wooCommerceApi,
			posStockUpdateProduct
		);

		try {
			await syncAndAssertStatus(
				cli,
				wcProducts,
				posStockUpdateProduct.name,
				'synced',
				product.id
			);

			await wooCommerceApi.updateProduct( product.id, {
				stock_quantity: 25,
			} );

			await syncAndAssertStatus(
				cli,
				wcProducts,
				posStockUpdateProduct.name,
				'synced',
				product.id
			);
			await expect
				.poll( () => getPosStockBalance( cli, zettleApi, product.id ), {
					message:
						'Assert PayPal POS stock balance is the updated WooCommerce stock (10 → 25)',
				} )
				.toBe( 25 );
		} finally {
			await deleteProduct( cli, product.id );
		}
	} );

	test( 'POS-588 | Disabling stock management updates POS inventory tracking @regression', async ( {
		wcProducts,
		wcStatusLogs,
		cli,
		wooCommerceApi,
	} ) => {
		const product = await createProduct(
			wooCommerceApi,
			posStockMgmtDisableProduct
		);

		try {
			await syncAndAssertStatus(
				cli,
				wcProducts,
				posStockMgmtDisableProduct.name,
				'synced',
				product.id
			);

			const posUuid = await getPosProductUuid( cli, product.id );
			expect(
				posUuid,
				'Assert synced product has a PayPal POS UUID in the ID map'
			).not.toBeNull();

			await wooCommerceApi.updateProduct( product.id, {
				manage_stock: false,
			} );

			await processQueue( cli );
			await wcProducts.visit();
			await wcProducts.assertProductSyncStatus(
				posStockMgmtDisableProduct.name,
				'synced',
				product.id
			);
			// PayPal POS still returns a stock balance after tracking stops, so the API
			// can't tell whether tracking is on. The plugin logs this line only after
			// PayPal POS accepted the stop-tracking call (SetInventoryTrackingJob).
			const logContent = await wcStatusLogs.viewLatestLogForSource(
				'paypal-point-of-sale'
			);
			expect(
				logContent,
				'Assert PayPal POS accepted the call to stop tracking this product'
			).toContain(
				`Disabled inventory tracking for WC product ${ product.id } with UUID ${ posUuid }`
			);
		} finally {
			await deleteProduct( cli, product.id );
		}
	} );

	test( 'POS-586 | WC Order Reduces POS Stock @regression', async ( {
		wcProducts,
		request,
		cli,
		wooCommerceApi,
	} ) => {
		const zettleApi = new ZettleApiClient( request );
		await zettleApi.authenticate(
			ZETTLE_CLIENT_ID,
			requireEnv( 'PAYPAL_POS_API_KEY' )
		);

		const product = await createProduct(
			wooCommerceApi,
			posOrderStockProduct
		);

		try {
			await syncAndAssertStatus(
				cli,
				wcProducts,
				posOrderStockProduct.name,
				'synced',
				product.id
			);

			const order = await wooCommerceApi.createOrder( {
				status: 'processing',
				line_items: [ { product_id: product.id, quantity: 3 } ],
			} );

			await processQueue( cli );
			await wcProducts.visit();
			await wcProducts.assertProductSyncStatus(
				posOrderStockProduct.name,
				'synced',
				product.id
			);
			await expect
				.poll( () => getPosStockBalance( cli, zettleApi, product.id ), {
					message:
						'Assert PayPal POS stock balance dropped by the 3 ordered items (20 → 17)',
				} )
				.toBe( 17 );

			await deleteOrder( wooCommerceApi, order.id );
		} finally {
			await deleteProduct( cli, product.id );
		}
	} );

	test( 'POS-585 | POS sale updates WooCommerce stock via InventoryBalanceChanged webhook @critical', async ( {
		wcProducts,
		page,
		cli,
		wooCommerceApi,
	} ) => {
		test.skip(
			! canReceiveWebhooks(),
			'WP_BASE_URL is not publicly reachable (no ngrok) — PayPal POS cannot register the webhook'
		);

		const product = await createProduct(
			wooCommerceApi,
			posWebhookStockProduct
		);

		try {
			await syncAndAssertStatus(
				cli,
				wcProducts,
				posWebhookStockProduct.name,
				'synced',
				product.id
			);

			const signingKey = await ensureWebhookRegistered( cli );
			expect(
				signingKey,
				'Assert webhook is registered and has a signing key'
			).toBeTruthy();

			const variantUuid = await getPosVariantUuid( cli, product.id );
			expect(
				variantUuid,
				'Assert synced product has a POS variant UUID in the ID map'
			).not.toBeNull();

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

			await expect
				.poll(
					async () => {
						const updated = await wooCommerceApi.getProduct(
							product.id
						);
						return updated.stock_quantity;
					},
					{
						message:
							'Assert stock quantity reflects the InventoryBalanceChanged webhook payload',
					}
				)
				.toBe( 18 );
		} finally {
			await deleteProduct( cli, product.id );
		}
	} );
} );
