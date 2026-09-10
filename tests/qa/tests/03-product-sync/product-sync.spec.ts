import { expect } from '@inpsyde/playwright-utils/build';
import {
	test,
	processQueue,
	syncAndAssertStatus,
	ensurePosTestReady,
	createProduct,
	deleteProduct,
	assertDeletionUnsyncsFromPos,
	getPosProductUuid,
	getPosVariantUuid,
	ZettleApiClient,
} from '../../utils';
import {
	rejectedSyncCases,
	posColumnTestProduct,
	posDraftProduct,
	posSimpleProductLifecycle,
	posDeleteMeProduct,
	posOriginalNameProduct,
	posExcludeMeProduct,
	posTypeChangeProduct,
	posShirtVariableProduct,
	posNoTaxRateProduct,
	posLastVariationProduct,
	posHiddenCatalogProduct,
	posSkuSyncProduct,
} from './_test-data';
import { testRejectedProductSync } from './_test-scenarios';

const ZETTLE_CLIENT_ID = 'de149dc7-44b5-4390-ab64-88e301771f06';

// ── tests ─────────────────────────────────────────────────────────────────────

test.describe( 'Product Sync (WC → POS)', () => {
	// Lets this file run standalone (npx playwright test .../product-sync.spec.ts) without
	// depending on setup:woocommerce/setup:paypal-pos having already run — see
	// ensurePosTestReady for why this is cheap when the full suite already did.
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
		}
	);

	test( 'POS-579 | Sync status column appears in product list; regression;', async ( {
		wcProducts,
		requestUtils,
		cli,
	} ) => {
		const product = await createProduct(
			requestUtils,
			posColumnTestProduct
		);

		try {
			await wcProducts.assertSyncStatusColumnVisible();
		} finally {
			await deleteProduct( cli, product.id );
		}
	} );

	test( 'POS-573 | Draft product is not synced to POS; regression;', async ( {
		wcProducts,
		requestUtils,
		cli,
	} ) => {
		const product = await createProduct( requestUtils, posDraftProduct );

		try {
			await wcProducts.visit( 'draft' );
			await wcProducts.assertProductSyncStatus(
				'POS-573 Draft Product',
				'not-published',
				product.id
			);
		} finally {
			await deleteProduct( cli, product.id );
		}
	} );

	test( 'POS-581 | Simple product created syncs to POS; critical;', async ( {
		wcProducts,
		requestUtils,
		cli,
	} ) => {
		test.setTimeout( 5 * 60_000 );

		if ( ! process.env.PAYPAL_POS_API_KEY ) {
			test.skip(
				true,
				'PAYPAL_POS_API_KEY not set — skipping live sync test'
			);
			return;
		}

		const product = await createProduct(
			requestUtils,
			posSimpleProductLifecycle
		);

		try {
			await syncAndAssertStatus(
				cli,
				wcProducts,
				'POS-581 Simple Product',
				'synced',
				product.id
			);

			await assertDeletionUnsyncsFromPos(
				requestUtils,
				cli,
				wcProducts,
				product.id,
				'POS-581 Simple Product'
			);
		} finally {
			await deleteProduct( cli, product.id );
		}
	} );

	test( 'POS-582 | Simple product deleted is removed from POS; regression;', async ( {
		wcProducts,
		requestUtils,
		cli,
	} ) => {
		test.setTimeout( 5 * 60_000 );

		if ( ! process.env.PAYPAL_POS_API_KEY ) {
			test.skip(
				true,
				'PAYPAL_POS_API_KEY not set — skipping live sync test'
			);
			return;
		}

		const product = await createProduct( requestUtils, posDeleteMeProduct );

		try {
			await syncAndAssertStatus(
				cli,
				wcProducts,
				'POS-582 Delete Me',
				'synced',
				product.id
			);

			await assertDeletionUnsyncsFromPos(
				requestUtils,
				cli,
				wcProducts,
				product.id,
				'POS-582 Delete Me'
			);
		} finally {
			await deleteProduct( cli, product.id );
		}
	} );

	test( 'POS-583 | Simple product name and price update syncs to POS; regression;', async ( {
		wcProducts,
		requestUtils,
		cli,
	} ) => {
		test.setTimeout( 5 * 60_000 );

		if ( ! process.env.PAYPAL_POS_API_KEY ) {
			test.skip(
				true,
				'PAYPAL_POS_API_KEY not set — skipping live sync test'
			);
			return;
		}

		const product = await createProduct(
			requestUtils,
			posOriginalNameProduct
		);

		try {
			await syncAndAssertStatus(
				cli,
				wcProducts,
				'POS-583 Original Name',
				'synced',
				product.id
			);

			await requestUtils.rest( {
				path: `/wc/v3/products/${ product.id }`,
				method: 'PUT',
				data: { name: 'POS-583 Updated Name', regular_price: '29.99' },
			} );

			await syncAndAssertStatus(
				cli,
				wcProducts,
				'POS-583 Updated Name',
				'synced',
				product.id
			);

			await assertDeletionUnsyncsFromPos(
				requestUtils,
				cli,
				wcProducts,
				product.id,
				'POS-583 Updated Name'
			);
		} finally {
			await deleteProduct( cli, product.id );
		}
	} );

	test( 'POS-578 | Excluded product is removed from POS and shows Excluded status; regression;', async ( {
		wcProducts,
		wcProductEdit,
		requestUtils,
		cli,
	} ) => {
		test.setTimeout( 5 * 60_000 );

		if ( ! process.env.PAYPAL_POS_API_KEY ) {
			test.skip(
				true,
				'PAYPAL_POS_API_KEY not set — skipping live sync test'
			);
			return;
		}

		const product = await createProduct(
			requestUtils,
			posExcludeMeProduct
		);

		try {
			await syncAndAssertStatus(
				cli,
				wcProducts,
				'POS-578 Exclude Me',
				'synced',
				product.id
			);

			await wcProductEdit.visitExisting( product.id );
			await wcProductEdit.setExcludeFromSync( true );
			await wcProductEdit.update();

			await processQueue( cli );

			await wcProducts.visit();
			await wcProducts.assertProductSyncStatus(
				'POS-578 Exclude Me',
				'excluded',
				product.id
			);
		} finally {
			await deleteProduct( cli, product.id );
		}
	} );

	test( 'POS-580 | Product type changed simple to variable re-syncs to POS; regression;', async ( {
		wcProducts,
		requestUtils,
		cli,
	} ) => {
		test.setTimeout( 5 * 60_000 );

		if ( ! process.env.PAYPAL_POS_API_KEY ) {
			test.skip(
				true,
				'PAYPAL_POS_API_KEY not set — skipping live sync test'
			);
			return;
		}

		const product = await createProduct(
			requestUtils,
			posTypeChangeProduct
		);

		try {
			await syncAndAssertStatus(
				cli,
				wcProducts,
				'POS-580 Type Change',
				'synced',
				product.id
			);

			await requestUtils.rest( {
				path: `/wc/v3/products/${ product.id }`,
				method: 'PUT',
				data: {
					type: 'variable',
					attributes: [
						{
							name: 'Color',
							variation: true,
							visible: true,
							options: [ 'Red', 'Blue' ],
						},
					],
				},
			} );

			await requestUtils.rest( {
				path: `/wc/v3/products/${ product.id }/variations`,
				method: 'POST',
				data: {
					attributes: [ { name: 'Color', option: 'Red' } ],
					regular_price: '25.00',
					manage_stock: true,
					stock_quantity: 5,
				},
			} );

			await syncAndAssertStatus(
				cli,
				wcProducts,
				'POS-580 Type Change',
				'synced',
				product.id
			);

			await assertDeletionUnsyncsFromPos(
				requestUtils,
				cli,
				wcProducts,
				product.id,
				'POS-580 Type Change'
			);
		} finally {
			await deleteProduct( cli, product.id );
		}
	} );

	test( 'POS-584 | Variable product full lifecycle — create, add variation, delete variation; regression;', async ( {
		wcProducts,
		requestUtils,
		cli,
	} ) => {
		test.setTimeout( 10 * 60_000 );

		if ( ! process.env.PAYPAL_POS_API_KEY ) {
			test.skip(
				true,
				'PAYPAL_POS_API_KEY not set — skipping live sync test'
			);
			return;
		}

		const product = await createProduct(
			requestUtils,
			posShirtVariableProduct
		);

		try {
			const variationS = await requestUtils.rest< { id: number } >( {
				path: `/wc/v3/products/${ product.id }/variations`,
				method: 'POST',
				data: {
					attributes: [ { name: 'Size', option: 'S' } ],
					regular_price: '15.00',
					manage_stock: true,
					stock_quantity: 20,
				},
			} );

			const variationL = await requestUtils.rest< { id: number } >( {
				path: `/wc/v3/products/${ product.id }/variations`,
				method: 'POST',
				data: {
					attributes: [ { name: 'Size', option: 'L' } ],
					regular_price: '17.00',
					manage_stock: true,
					stock_quantity: 15,
				},
			} );

			await syncAndAssertStatus(
				cli,
				wcProducts,
				'POS-584 T-Shirt',
				'synced',
				product.id
			);

			const variationXL = await requestUtils.rest< { id: number } >( {
				path: `/wc/v3/products/${ product.id }/variations`,
				method: 'POST',
				data: {
					attributes: [ { name: 'Size', option: 'XL' } ],
					regular_price: '19.00',
					manage_stock: true,
					stock_quantity: 10,
				},
			} );

			await syncAndAssertStatus(
				cli,
				wcProducts,
				'POS-584 T-Shirt',
				'synced',
				product.id
			);

			await requestUtils.rest( {
				path: `/wc/v3/products/${ product.id }/variations/${ variationXL.id }`,
				method: 'DELETE',
				params: { force: true },
			} );

			await processQueue( cli );
			await wcProducts.visit();
			await wcProducts.assertProductSyncStatus(
				'POS-584 T-Shirt',
				'synced',
				product.id
			);

			await requestUtils.rest( {
				path: `/wc/v3/products/${ product.id }/variations/${ variationS.id }`,
				method: 'DELETE',
				params: { force: true },
			} );
			await requestUtils.rest( {
				path: `/wc/v3/products/${ product.id }/variations/${ variationL.id }`,
				method: 'DELETE',
				params: { force: true },
			} );

			await assertDeletionUnsyncsFromPos(
				requestUtils,
				cli,
				wcProducts,
				product.id,
				'POS-584 T-Shirt'
			);
		} finally {
			await deleteProduct( cli, product.id );
		}
	} );

	for ( const rejectedSyncCase of rejectedSyncCases ) {
		testRejectedProductSync( rejectedSyncCase );
	}

	test( 'POS-650 | Product with invalid/unconfigured tax class is not synced; regression;', async ( {
		wcProducts,
		wcStatusLogs,
		requestUtils,
		request,
		cli,
	} ) => {
		test.setTimeout( 5 * 60_000 );

		if ( ! process.env.PAYPAL_POS_API_KEY ) {
			test.skip(
				true,
				'PAYPAL_POS_API_KEY not set — skipping live sync test'
			);
			return;
		}

		const PRODUCT_NAME = posNoTaxRateProduct.name;

		const zettleApi = new ZettleApiClient( request );
		await zettleApi.authenticate(
			ZETTLE_CLIENT_ID,
			process.env.PAYPAL_POS_API_KEY
		);

		const taxClass = await requestUtils.rest< { slug: string } >( {
			path: '/wc/v3/taxes/classes',
			method: 'POST',
			data: { name: 'POS-650 No Rates' },
		} );

		const product = await createProduct( requestUtils, {
			...posNoTaxRateProduct,
			tax_class: taxClass.slug,
		} );

		try {
			await syncAndAssertStatus(
				cli,
				wcProducts,
				PRODUCT_NAME,
				'no-tax-rate',
				product.id
			);

			expect(
				await zettleApi.findProductByName( PRODUCT_NAME ),
				'product with no tax rate should not exist in the PayPal POS product library'
			).toBeUndefined();

			const logContent = await wcStatusLogs.viewLatestLogForSource(
				'paypal-point-of-sale'
			);
			expect(
				logContent,
				'expected the plugin log to record the specific "No tax rate" rejection reason'
			).toContain( 'No tax rate' );
		} finally {
			await deleteProduct( cli, product.id );
			await requestUtils.rest( {
				path: `/wc/v3/taxes/classes/${ taxClass.slug }`,
				method: 'DELETE',
				params: { force: true },
			} );

			await zettleApi.deleteProductByName( PRODUCT_NAME );
		}
	} );

	test( "POS-662 | Deleting a variable product's last variation removes it from PayPal POS; regression;", async ( {
		wcProducts,
		requestUtils,
		request,
		cli,
	} ) => {
		test.setTimeout( 5 * 60_000 );

		if ( ! process.env.PAYPAL_POS_API_KEY ) {
			test.skip(
				true,
				'PAYPAL_POS_API_KEY not set — skipping live sync test'
			);
			return;
		}

		const PRODUCT_NAME = posLastVariationProduct.name;

		const zettleApi = new ZettleApiClient( request );
		await zettleApi.authenticate(
			ZETTLE_CLIENT_ID,
			process.env.PAYPAL_POS_API_KEY
		);

		const product = await createProduct(
			requestUtils,
			posLastVariationProduct
		);

		try {
			const variation = await requestUtils.rest< { id: number } >( {
				path: `/wc/v3/products/${ product.id }/variations`,
				method: 'POST',
				data: {
					attributes: [ { name: 'Size', option: 'S' } ],
					regular_price: '12.00',
					manage_stock: true,
					stock_quantity: 5,
				},
			} );

			await syncAndAssertStatus(
				cli,
				wcProducts,
				PRODUCT_NAME,
				'synced',
				product.id
			);

			const productUuidBefore = await getPosProductUuid(
				cli,
				product.id
			);
			expect(
				productUuidBefore,
				'product should have a POS UUID mapping before its last variation is deleted'
			).not.toBeNull();

			// Deleting the only remaining variation leaves the variable product with none —
			// DeleteVariableWithoutVariationsListener (paypal-pos-sync) detects this via
			// get_available_variations() and deletes the whole remote product, not just the variant.
			await requestUtils.rest( {
				path: `/wc/v3/products/${ product.id }/variations/${ variation.id }`,
				method: 'DELETE',
				params: { force: true },
			} );

			await processQueue( cli );

			expect(
				await zettleApi.findProductByName( PRODUCT_NAME ),
				'product should no longer exist in the PayPal POS product library once its last variation is deleted'
			).toBeUndefined();

			const productUuidAfter = await getPosProductUuid( cli, product.id );
			expect(
				productUuidAfter,
				'no orphaned product UUID mapping should remain after the last variation is deleted'
			).toBeNull();

			const variantUuidAfter = await getPosVariantUuid(
				cli,
				variation.id
			);
			expect(
				variantUuidAfter,
				'no orphaned variant UUID mapping should remain after the last variation is deleted'
			).toBeNull();
		} finally {
			await deleteProduct( cli, product.id );
			await zettleApi.deleteProductByName( PRODUCT_NAME );
		}
	} );

	test( 'POS-651 | Product with Catalog visibility "Hidden" is not synced; regression;', async ( {
		wcProducts,
		requestUtils,
		request,
		cli,
	} ) => {
		test.setTimeout( 5 * 60_000 );

		if ( ! process.env.PAYPAL_POS_API_KEY ) {
			test.skip(
				true,
				'PAYPAL_POS_API_KEY not set — skipping live sync test'
			);
			return;
		}

		const PRODUCT_NAME = posHiddenCatalogProduct.name;

		const zettleApi = new ZettleApiClient( request );
		await zettleApi.authenticate(
			ZETTLE_CLIENT_ID,
			process.env.PAYPAL_POS_API_KEY
		);

		const product = await createProduct(
			requestUtils,
			posHiddenCatalogProduct
		);

		try {
			await syncAndAssertStatus(
				cli,
				wcProducts,
				PRODUCT_NAME,
				'not-visible',
				product.id
			);

			expect(
				await zettleApi.findProductByName( PRODUCT_NAME ),
				'product with hidden catalog visibility should not exist in the PayPal POS product library'
			).toBeUndefined();
		} finally {
			await deleteProduct( cli, product.id );
			await zettleApi.deleteProductByName( PRODUCT_NAME );
		}
	} );

	test( 'POS-664 | Product SKU syncs to POS and stays in sync after an update; regression;', async ( {
		wcProducts,
		requestUtils,
		request,
		cli,
	} ) => {
		test.setTimeout( 5 * 60_000 );

		if ( ! process.env.PAYPAL_POS_API_KEY ) {
			test.skip(
				true,
				'PAYPAL_POS_API_KEY not set — skipping live sync test'
			);
			return;
		}

		const PRODUCT_NAME = posSkuSyncProduct.name;

		const zettleApi = new ZettleApiClient( request );
		await zettleApi.authenticate(
			ZETTLE_CLIENT_ID,
			process.env.PAYPAL_POS_API_KEY
		);

		const product = await createProduct( requestUtils, posSkuSyncProduct );

		try {
			await syncAndAssertStatus(
				cli,
				wcProducts,
				PRODUCT_NAME,
				'synced',
				product.id
			);

			const foundAfterCreate =
				await zettleApi.findProductByName( PRODUCT_NAME );
			expect(
				foundAfterCreate?.variants?.[ 0 ]?.sku,
				'SKU should be synced to the PayPal POS product library on create'
			).toBe( 'WC-SKU-0001' );

			await requestUtils.rest( {
				path: `/wc/v3/products/${ product.id }`,
				method: 'PUT',
				data: { sku: 'WC-SKU-0002' },
			} );

			await syncAndAssertStatus(
				cli,
				wcProducts,
				PRODUCT_NAME,
				'synced',
				product.id
			);

			const foundAfterUpdate =
				await zettleApi.findProductByName( PRODUCT_NAME );
			expect(
				foundAfterUpdate?.variants?.[ 0 ]?.sku,
				'SKU should be updated in the PayPal POS product library after a product update'
			).toBe( 'WC-SKU-0002' );
		} finally {
			await deleteProduct( cli, product.id );
			await zettleApi.deleteProductByName( PRODUCT_NAME );
		}
	} );
} );
