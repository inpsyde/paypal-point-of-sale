import { expect } from '@inpsyde/playwright-utils/build';
import {
	test,
	requireEnv,
	processQueue,
	syncAndAssertStatus,
	ensurePosTestReady,
	createProduct,
	deleteProduct,
	assertDeletionUnsyncsFromPos,
	getPosProductUuid,
	getSyncedPosProduct,
	getPosVariantUuid,
	ZettleApiClient,
	getCurrency,
	setCurrency,
	getPriceSyncStrategy,
	setPriceSyncStrategy,
	PriceSyncMode,
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
	posPrivateProduct,
	posCurrencyMismatchProduct,
	posUpdatedProductName,
	posNoRatesTaxClassName,
	posSkuOnCreate,
	posSkuOnUpdate,
	testRejectedProductSync,
} from './src';

const ZETTLE_CLIENT_ID = 'de149dc7-44b5-4390-ab64-88e301771f06';

// ── tests ─────────────────────────────────────────────────────────────────────

test.describe( 'Product Sync (WC → POS)', () => {
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

	test( 'POS-579 | Sync status column appears in product list @regression', async ( {
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

	test( 'POS-573 | Draft product is not synced to POS @regression', async ( {
		wcProducts,
		requestUtils,
		cli,
	} ) => {
		const product = await createProduct( requestUtils, posDraftProduct );

		try {
			await wcProducts.visit( 'draft' );
			await wcProducts.assertProductSyncStatus(
				posDraftProduct.name,
				'not-published',
				product.id
			);
		} finally {
			await deleteProduct( cli, product.id );
		}
	} );

	test( 'POS-581 | Simple product created syncs to POS @critical', async ( {
		wcProducts,
		requestUtils,
		request,
		cli,
	} ) => {
		test.setTimeout( 5 * 60_000 );

		const apiKey = requireEnv( 'PAYPAL_POS_API_KEY' );

		const zettleApi = new ZettleApiClient( request );
		await zettleApi.authenticate( ZETTLE_CLIENT_ID, apiKey );

		const product = await createProduct(
			requestUtils,
			posSimpleProductLifecycle
		);

		try {
			await syncAndAssertStatus(
				cli,
				wcProducts,
				posSimpleProductLifecycle.name,
				'synced',
				product.id
			);

			await assertDeletionUnsyncsFromPos(
				requestUtils,
				cli,
				wcProducts,
				zettleApi,
				product.id,
				posSimpleProductLifecycle.name
			);
		} finally {
			await deleteProduct( cli, product.id );
		}
	} );

	test( 'POS-582 | Simple product deleted is removed from POS @regression', async ( {
		wcProducts,
		requestUtils,
		request,
		cli,
	} ) => {
		test.setTimeout( 5 * 60_000 );

		const apiKey = requireEnv( 'PAYPAL_POS_API_KEY' );

		const zettleApi = new ZettleApiClient( request );
		await zettleApi.authenticate( ZETTLE_CLIENT_ID, apiKey );

		const product = await createProduct( requestUtils, posDeleteMeProduct );

		try {
			await syncAndAssertStatus(
				cli,
				wcProducts,
				posDeleteMeProduct.name,
				'synced',
				product.id
			);

			await assertDeletionUnsyncsFromPos(
				requestUtils,
				cli,
				wcProducts,
				zettleApi,
				product.id,
				posDeleteMeProduct.name
			);
		} finally {
			await deleteProduct( cli, product.id );
		}
	} );

	test( 'POS-583 | Simple product name and price update syncs to POS @regression', async ( {
		wcProducts,
		requestUtils,
		request,
		cli,
	} ) => {
		test.setTimeout( 5 * 60_000 );

		const apiKey = requireEnv( 'PAYPAL_POS_API_KEY' );

		const zettleApi = new ZettleApiClient( request );
		await zettleApi.authenticate( ZETTLE_CLIENT_ID, apiKey );

		const product = await createProduct(
			requestUtils,
			posOriginalNameProduct
		);

		try {
			await syncAndAssertStatus(
				cli,
				wcProducts,
				posOriginalNameProduct.name,
				'synced',
				product.id
			);

			await requestUtils.rest( {
				path: `/wc/v3/products/${ product.id }`,
				method: 'PUT',
				data: { name: posUpdatedProductName, regular_price: '29.99' },
			} );

			await syncAndAssertStatus(
				cli,
				wcProducts,
				posUpdatedProductName,
				'synced',
				product.id
			);

			const remoteProduct = await getSyncedPosProduct(
				cli,
				zettleApi,
				product.id
			);
			expect(
				remoteProduct,
				'Assert renamed product exists in the PayPal POS product library'
			).toBeDefined();
			// PayPal POS stores amounts in minor units (29.99 → 2999).
			expect(
				remoteProduct?.variants?.[ 0 ]?.price?.amount,
				'Assert updated price was synced to the PayPal POS product library'
			).toBe( 2999 );

			await assertDeletionUnsyncsFromPos(
				requestUtils,
				cli,
				wcProducts,
				zettleApi,
				product.id,
				posUpdatedProductName
			);
		} finally {
			await deleteProduct( cli, product.id );
		}
	} );

	test( 'POS-578 | Excluded product is removed from POS and shows Excluded status @regression', async ( {
		wcProducts,
		wcProductEdit,
		requestUtils,
		request,
		cli,
	} ) => {
		test.setTimeout( 5 * 60_000 );

		const apiKey = requireEnv( 'PAYPAL_POS_API_KEY' );

		const zettleApi = new ZettleApiClient( request );
		await zettleApi.authenticate( ZETTLE_CLIENT_ID, apiKey );

		const product = await createProduct(
			requestUtils,
			posExcludeMeProduct
		);

		try {
			await syncAndAssertStatus(
				cli,
				wcProducts,
				posExcludeMeProduct.name,
				'synced',
				product.id
			);

			const posUuid = await getPosProductUuid( cli, product.id );
			expect(
				posUuid,
				'Assert synced product has a PayPal POS UUID in the ID map'
			).not.toBeNull();

			await wcProductEdit.visitExisting( product.id );
			await wcProductEdit.setExcludeFromSync( true );
			await wcProductEdit.update();

			await processQueue( cli );

			await wcProducts.visit();
			await wcProducts.assertProductSyncStatus(
				posExcludeMeProduct.name,
				'excluded',
				product.id
			);
			await expect
				.poll( () => zettleApi.getProduct( posUuid as string ), {
					message:
						'Assert excluded product is removed from the PayPal POS product library',
				} )
				.toBeUndefined();
		} finally {
			await deleteProduct( cli, product.id );
		}
	} );

	test( 'POS-580 | Product type changed simple to variable re-syncs to POS @regression', async ( {
		wcProducts,
		requestUtils,
		request,
		cli,
	} ) => {
		test.setTimeout( 5 * 60_000 );

		const apiKey = requireEnv( 'PAYPAL_POS_API_KEY' );

		const zettleApi = new ZettleApiClient( request );
		await zettleApi.authenticate( ZETTLE_CLIENT_ID, apiKey );

		const product = await createProduct(
			requestUtils,
			posTypeChangeProduct
		);

		try {
			await syncAndAssertStatus(
				cli,
				wcProducts,
				posTypeChangeProduct.name,
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
				posTypeChangeProduct.name,
				'synced',
				product.id
			);

			// The simple product cost 20.00; the only variation costs 25.00.
			await expect
				.poll(
					async () =>
						(
							await getSyncedPosProduct(
								cli,
								zettleApi,
								product.id
							)
						)?.variants?.map(
							( variant ) => variant.price?.amount
						),
					{
						message:
							'Assert PayPal POS has the variation (25.00) instead of the simple product (20.00)',
					}
				)
				.toEqual( [ 2500 ] );

			await assertDeletionUnsyncsFromPos(
				requestUtils,
				cli,
				wcProducts,
				zettleApi,
				product.id,
				posTypeChangeProduct.name
			);
		} finally {
			await deleteProduct( cli, product.id );
		}
	} );

	test( 'POS-584 | Variable product full lifecycle — create, add variation, delete variation @regression', async ( {
		wcProducts,
		requestUtils,
		request,
		cli,
	} ) => {
		test.setTimeout( 10 * 60_000 );

		const apiKey = requireEnv( 'PAYPAL_POS_API_KEY' );

		const zettleApi = new ZettleApiClient( request );
		await zettleApi.authenticate( ZETTLE_CLIENT_ID, apiKey );

		const product = await createProduct(
			requestUtils,
			posShirtVariableProduct
		);

		// Each size has its own price (S 15.00, L 17.00, XL 19.00), so the prices
		// tell which variants PayPal POS has.
		const posVariantPrices = async () =>
			(
				await getSyncedPosProduct( cli, zettleApi, product.id )
			)?.variants
				?.map( ( variant ) => variant.price?.amount ?? 0 )
				.sort( ( a, b ) => a - b );

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
				posShirtVariableProduct.name,
				'synced',
				product.id
			);
			await expect
				.poll( posVariantPrices, {
					message: 'Assert PayPal POS has the S and L variants',
				} )
				.toEqual( [ 1500, 1700 ] );

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
				posShirtVariableProduct.name,
				'synced',
				product.id
			);
			await expect
				.poll( posVariantPrices, {
					message: 'Assert PayPal POS has the added XL variant',
				} )
				.toEqual( [ 1500, 1700, 1900 ] );

			await requestUtils.rest( {
				path: `/wc/v3/products/${ product.id }/variations/${ variationXL.id }`,
				method: 'DELETE',
				params: { force: true },
			} );

			await processQueue( cli );
			await wcProducts.visit();
			await wcProducts.assertProductSyncStatus(
				posShirtVariableProduct.name,
				'synced',
				product.id
			);
			await expect
				.poll( posVariantPrices, {
					message:
						'Assert PayPal POS no longer has the deleted XL variant',
				} )
				.toEqual( [ 1500, 1700 ] );

			// Read the UUID first: deleting the last variation removes the product
			// from PayPal POS and from the ID map.
			const posUuid = await getPosProductUuid( cli, product.id );
			expect(
				posUuid,
				'Assert synced product has a PayPal POS UUID in the ID map'
			).not.toBeNull();

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

			await processQueue( cli );
			await expect
				.poll( () => zettleApi.getProduct( posUuid as string ), {
					message:
						'Assert PayPal POS removed the product after its last variation was deleted',
				} )
				.toBeUndefined();

			await requestUtils.rest( {
				path: `/wc/v3/products/${ product.id }`,
				method: 'DELETE',
				params: { force: false },
			} );
			await processQueue( cli );
			await wcProducts.visit( 'trash' );
			await wcProducts.assertProductSyncStatus(
				posShirtVariableProduct.name,
				'not-synced',
				product.id
			);
		} finally {
			await deleteProduct( cli, product.id );
		}
	} );

	for ( const rejectedSyncCase of rejectedSyncCases ) {
		testRejectedProductSync( rejectedSyncCase );
	}

	test( 'POS-650 | Product With Invalid/Unconfigured Tax Class Is Not Synced @regression', async ( {
		wcProducts,
		wcStatusLogs,
		requestUtils,
		request,
		cli,
	} ) => {
		test.setTimeout( 5 * 60_000 );

		const apiKey = requireEnv( 'PAYPAL_POS_API_KEY' );

		const PRODUCT_NAME = posNoTaxRateProduct.name;

		const zettleApi = new ZettleApiClient( request );
		await zettleApi.authenticate( ZETTLE_CLIENT_ID, apiKey );

		const taxClass = await requestUtils.rest< { slug: string } >( {
			path: '/wc/v3/taxes/classes',
			method: 'POST',
			data: { name: posNoRatesTaxClassName },
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
				'Assert product with no tax rate does not exist in the PayPal POS product library'
			).toBeUndefined();

			const logContent = await wcStatusLogs.viewLatestLogForSource(
				'paypal-point-of-sale'
			);
			// The log file holds the whole day, so match this product's line
			// (ExportProductJob: "Skipping <class> <id>, not syncable because: ...").
			expect(
				logContent,
				'Assert plugin log records the "No tax rate" rejection reason for this product'
			).toMatch(
				new RegExp(
					`\\b${ product.id }, not syncable because: [^\\n]*No tax rate`
				)
			);
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

	test( "POS-662 | Deleting a Variable Product's Last Variation Removes It From PayPal POS (Cleanup) @regression", async ( {
		wcProducts,
		requestUtils,
		request,
		cli,
	} ) => {
		test.setTimeout( 5 * 60_000 );

		const apiKey = requireEnv( 'PAYPAL_POS_API_KEY' );

		const PRODUCT_NAME = posLastVariationProduct.name;

		const zettleApi = new ZettleApiClient( request );
		await zettleApi.authenticate( ZETTLE_CLIENT_ID, apiKey );

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
				'Assert product has a POS UUID mapping before its last variation is deleted'
			).not.toBeNull();

			await requestUtils.rest( {
				path: `/wc/v3/products/${ product.id }/variations/${ variation.id }`,
				method: 'DELETE',
				params: { force: true },
			} );

			await processQueue( cli );

			expect(
				await zettleApi.getProduct( productUuidBefore as string ),
				'Assert product no longer exists in the PayPal POS product library once its last variation is deleted'
			).toBeUndefined();

			const productUuidAfter = await getPosProductUuid( cli, product.id );
			expect(
				productUuidAfter,
				'Assert no orphaned product UUID mapping remains after the last variation is deleted'
			).toBeNull();

			const variantUuidAfter = await getPosVariantUuid(
				cli,
				variation.id
			);
			expect(
				variantUuidAfter,
				'Assert no orphaned variant UUID mapping remains after the last variation is deleted'
			).toBeNull();
		} finally {
			await deleteProduct( cli, product.id );
			await zettleApi.deleteProductByName( PRODUCT_NAME );
		}
	} );

	test( 'POS-651 | Product With Hidden Catalog Visibility Is Not Synced @regression', async ( {
		wcProducts,
		requestUtils,
		request,
		cli,
	} ) => {
		test.setTimeout( 5 * 60_000 );

		const apiKey = requireEnv( 'PAYPAL_POS_API_KEY' );

		const PRODUCT_NAME = posHiddenCatalogProduct.name;

		const zettleApi = new ZettleApiClient( request );
		await zettleApi.authenticate( ZETTLE_CLIENT_ID, apiKey );

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
				'Assert product with hidden catalog visibility does not exist in the PayPal POS product library'
			).toBeUndefined();
		} finally {
			await deleteProduct( cli, product.id );
			await zettleApi.deleteProductByName( PRODUCT_NAME );
		}
	} );

	test( 'POS-664 | Product SKU syncs to POS and stays in sync after an update @regression', async ( {
		wcProducts,
		requestUtils,
		request,
		cli,
	} ) => {
		test.setTimeout( 5 * 60_000 );

		const apiKey = requireEnv( 'PAYPAL_POS_API_KEY' );

		const PRODUCT_NAME = posSkuSyncProduct.name;

		const zettleApi = new ZettleApiClient( request );
		await zettleApi.authenticate( ZETTLE_CLIENT_ID, apiKey );

		const product = await createProduct( requestUtils, posSkuSyncProduct );

		try {
			await syncAndAssertStatus(
				cli,
				wcProducts,
				PRODUCT_NAME,
				'synced',
				product.id
			);

			const foundAfterCreate = await getSyncedPosProduct(
				cli,
				zettleApi,
				product.id
			);
			expect(
				foundAfterCreate?.variants?.[ 0 ]?.sku,
				'Assert SKU is synced to the PayPal POS product library on create'
			).toBe( posSkuOnCreate );

			await requestUtils.rest( {
				path: `/wc/v3/products/${ product.id }`,
				method: 'PUT',
				data: { sku: posSkuOnUpdate },
			} );

			await syncAndAssertStatus(
				cli,
				wcProducts,
				PRODUCT_NAME,
				'synced',
				product.id
			);

			const foundAfterUpdate = await getSyncedPosProduct(
				cli,
				zettleApi,
				product.id
			);
			expect(
				foundAfterUpdate?.variants?.[ 0 ]?.sku,
				'Assert SKU is updated in the PayPal POS product library after a product update'
			).toBe( posSkuOnUpdate );
		} finally {
			await deleteProduct( cli, product.id );
			await zettleApi.deleteProductByName( PRODUCT_NAME );
		}
	} );

	test( 'POS-665 | Product Set to Private Visibility Is Not Synced @regression', async ( {
		wcProducts,
		requestUtils,
		request,
		cli,
	} ) => {
		test.setTimeout( 5 * 60_000 );

		const apiKey = requireEnv( 'PAYPAL_POS_API_KEY' );

		const PRODUCT_NAME = posPrivateProduct.name;

		const zettleApi = new ZettleApiClient( request );
		await zettleApi.authenticate( ZETTLE_CLIENT_ID, apiKey );

		const product = await createProduct( requestUtils, posPrivateProduct );

		try {
			await syncAndAssertStatus(
				cli,
				wcProducts,
				PRODUCT_NAME,
				'not-published',
				product.id
			);

			expect(
				await zettleApi.findProductByName( PRODUCT_NAME ),
				'Assert private product does not exist in the PayPal POS product library'
			).toBeUndefined();
		} finally {
			await deleteProduct( cli, product.id );
			await zettleApi.deleteProductByName( PRODUCT_NAME );
		}
	} );

	test( 'POS-590 | Price Sync After Currency Change @regression', async ( {
		wcProducts,
		wcProductEdit,
		posSettings,
		requestUtils,
		wooCommerceApi,
		request,
		cli,
	} ) => {
		// Known bug POS-679 (https://inpsyde.atlassian.net/browse/POS-679): the settings page
		// still shows "Prices syncing: Yes" after the currency mismatch. Remove once it's fixed;
		// Playwright reports this test as failing as soon as it passes.
		test.fail( true, 'Known bug POS-679' );
		test.setTimeout( 5 * 60_000 );

		const apiKey = requireEnv( 'PAYPAL_POS_API_KEY' );

		const PRODUCT_NAME = posCurrencyMismatchProduct.name;
		// Arbitrary currency that won't match the connected PayPal POS sandbox account.
		const MISMATCHED_CURRENCY = 'JPY';

		const zettleApi = new ZettleApiClient( request );
		await zettleApi.authenticate( ZETTLE_CLIENT_ID, apiKey );

		const originalCurrency = await getCurrency( wooCommerceApi );
		const originalPriceSyncStrategy = await getPriceSyncStrategy( cli );
		const product = await createProduct(
			requestUtils,
			posCurrencyMismatchProduct
		);

		try {
			await setCurrency( wooCommerceApi, MISMATCHED_CURRENCY );

			// Any wp-admin request re-evaluates the currency comparison and force-disables
			// price sync (SyncModule::run()) — visiting the POS settings page is enough.
			await posSettings.visit();
			await posSettings.assertPriceSyncDisabled();
			expect(
				await getPriceSyncStrategy( cli ),
				'Assert price sync strategy is forced to disabled after currency mismatch'
			).toBe( PriceSyncMode.DISABLED );

			// Manually re-enabling it doesn't stick while the currencies still mismatch —
			// the next admin request re-forces it back to disabled.
			await setPriceSyncStrategy( cli, PriceSyncMode.ENABLED );
			await posSettings.visit();
			expect(
				await getPriceSyncStrategy( cli ),
				'Assert manually re-enabled price sync reverts to disabled on next admin load'
			).toBe( PriceSyncMode.DISABLED );

			await wcProductEdit.visitExisting( product.id );
			await wcProductEdit.setRegularPrice( '30.00' );
			await wcProductEdit.update();
			await wcProductEdit.assertNoErrors();

			await syncAndAssertStatus(
				cli,
				wcProducts,
				PRODUCT_NAME,
				'synced',
				product.id
			);

			const remoteProduct = await getSyncedPosProduct(
				cli,
				zettleApi,
				product.id
			);
			expect(
				remoteProduct,
				'Assert product still syncs to the PayPal POS product library'
			).toBeDefined();
			expect(
				remoteProduct?.variants?.[ 0 ]?.price?.amount,
				'Assert price was not synced to the PayPal POS product library'
			).toBeFalsy();
		} finally {
			await setCurrency( wooCommerceApi, originalCurrency );
			// The plugin persisted DISABLED while the currency mismatched and never re-enables
			// it. Restore only after the currency, or the next admin request re-forces it.
			await setPriceSyncStrategy( cli, originalPriceSyncStrategy );
			await deleteProduct( cli, product.id );
			await zettleApi.deleteProductByName( PRODUCT_NAME );
		}
	} );
} );
