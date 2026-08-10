import { expect } from '@inpsyde/playwright-utils/build';
import {
	test,
	processQueue,
	syncProduct,
	ensurePosTestReady,
	createProduct,
	deleteProduct,
	assertDeletionUnsyncsFromPos,
	ZettleApiClient,
	type ZettleProduct,
} from '../../utils';

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
		const product = await createProduct( requestUtils, {
			name: 'POS-579 Column Test',
			regular_price: '5.00',
		} );

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
		const product = await createProduct( requestUtils, {
			name: 'POS-573 Draft Product',
			status: 'draft',
			regular_price: '9.99',
		} );

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

		const product = await createProduct( requestUtils, {
			name: 'POS-581 Simple Product',
			regular_price: '19.99',
			manage_stock: true,
			stock_quantity: 10,
		} );

		try {
			await syncProduct( cli, product.id );
			await wcProducts.visit();
			await wcProducts.assertProductSyncStatus(
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

		const product = await createProduct( requestUtils, {
			name: 'POS-582 Delete Me',
			regular_price: '5.00',
		} );

		try {
			await syncProduct( cli, product.id );
			await wcProducts.visit();
			await wcProducts.assertProductSyncStatus(
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

		const product = await createProduct( requestUtils, {
			name: 'POS-583 Original Name',
			regular_price: '10.00',
		} );

		try {
			await syncProduct( cli, product.id );
			await wcProducts.visit();
			await wcProducts.assertProductSyncStatus(
				'POS-583 Original Name',
				'synced',
				product.id
			);

			await requestUtils.rest( {
				path: `/wc/v3/products/${ product.id }`,
				method: 'PUT',
				data: { name: 'POS-583 Updated Name', regular_price: '29.99' },
			} );

			await syncProduct( cli, product.id );
			await wcProducts.visit();
			await wcProducts.assertProductSyncStatus(
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

		const product = await createProduct( requestUtils, {
			name: 'POS-578 Exclude Me',
			regular_price: '15.00',
		} );

		try {
			await syncProduct( cli, product.id );
			await wcProducts.visit();
			await wcProducts.assertProductSyncStatus(
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

		const product = await createProduct( requestUtils, {
			name: 'POS-580 Type Change',
			regular_price: '20.00',
		} );

		try {
			await syncProduct( cli, product.id );
			await wcProducts.visit();
			await wcProducts.assertProductSyncStatus(
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

			await syncProduct( cli, product.id );
			await wcProducts.visit();
			await wcProducts.assertProductSyncStatus(
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

		const product = await createProduct( requestUtils, {
			name: 'POS-584 T-Shirt',
			type: 'variable',
			attributes: [
				{
					name: 'Size',
					variation: true,
					visible: true,
					options: [ 'S', 'L', 'XL' ],
				},
			],
		} );

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

			await syncProduct( cli, product.id );
			await wcProducts.visit();
			await wcProducts.assertProductSyncStatus(
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

			await syncProduct( cli, product.id );
			await wcProducts.visit();
			await wcProducts.assertProductSyncStatus(
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

	test( 'POS-643 | Unsupported product type shows Unsupported status; regression;', async ( {
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

		// Only simple/variable are in the plugin's allowed-product-types list
		// (paypal-pos-sync/services.php) — grouped products fall outside it.
		const product = await createProduct( requestUtils, {
			name: 'POS-643 Grouped Product',
			type: 'grouped',
		} );

		try {
			await syncProduct( cli, product.id );
			await wcProducts.visit();
			await wcProducts.assertProductSyncStatus(
				'POS-643 Grouped Product',
				'unsupported-product-type',
				product.id
			);
		} finally {
			await deleteProduct( cli, product.id );
		}
	} );

	test( 'POS-644 | Variable product with more than 3 variation attributes is rejected; regression;', async ( {
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

		// Zettle allows at most 3 variant option definitions per product
		// (VariantOptionDefinitionsValidator::MAXIMUM_DEFINITIONS_AMOUNT) — 4 attributes
		// should trip that limit.
		const attributeNames = [ 'Color', 'Size', 'Material', 'Style' ];
		const product = await createProduct( requestUtils, {
			name: 'POS-644 Too Many Attributes',
			type: 'variable',
			attributes: attributeNames.map( ( name ) => ( {
				name,
				variation: true,
				visible: true,
				options: [ 'A', 'B' ],
			} ) ),
		} );

		try {
			await requestUtils.rest( {
				path: `/wc/v3/products/${ product.id }/variations`,
				method: 'POST',
				data: {
					attributes: attributeNames.map( ( name ) => ( {
						name,
						option: 'A',
					} ) ),
					regular_price: '10.00',
				},
			} );

			await syncProduct( cli, product.id );
			await wcProducts.visit();
			await wcProducts.assertProductSyncStatus(
				'POS-644 Too Many Attributes',
				'too-many-variant-options',
				product.id
			);
		} finally {
			await deleteProduct( cli, product.id );
		}
	} );

	test( 'POS-645 | Variable product with more than 99 variations is rejected; regression;', async ( {
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

		// Zettle allows at most 99 variants per product (ProductValidator::MAXIMUM_VARIANTS_AMOUNT)
		// — 100 variations should trip that limit. Uses the variations batch endpoint to
		// avoid 100 sequential REST round-trips.
		const VARIATION_COUNT = 100;
		const sizes = Array.from(
			{ length: VARIATION_COUNT },
			( _, i ) => `Size ${ i + 1 }`
		);

		const product = await createProduct( requestUtils, {
			name: 'POS-645 Too Many Variations',
			type: 'variable',
			attributes: [
				{
					name: 'Size',
					variation: true,
					visible: true,
					options: sizes,
				},
			],
		} );

		try {
			await requestUtils.rest( {
				path: `/wc/v3/products/${ product.id }/variations/batch`,
				method: 'POST',
				data: {
					create: sizes.map( ( size ) => ( {
						attributes: [ { name: 'Size', option: size } ],
						regular_price: '10.00',
					} ) ),
				},
				// Default actionTimeout (30s) isn't enough here: the plugin's lifecycle-event
				// hooks rebuild the full product + variant DTO on every single variation save
				// (see ProductValidator/VariantBuilder), so cost grows with variation count.
				timeout: 5 * 60_000,
			} );

			await syncProduct( cli, product.id );
			await wcProducts.visit();
			await wcProducts.assertProductSyncStatus(
				'POS-645 Too Many Variations',
				'too-many-variants',
				product.id
			);
		} finally {
			await deleteProduct( cli, product.id );
		}
	} );

	test( 'POS-647 | Simple product with title exceeding 256 characters is rejected and not synced; regression;', async ( {
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

		// Zettle rejects the create call with a server-side CONSTRAINT_VIOLATION
		// on `name` (size must be 1-256) — the plugin swallows that exception
		// (ExportProductJob::attemptCreate) without persisting a status, so the
		// product resolves to the generic never-synced bucket. See POS-649 for
		// the underlying gap where this specific reason isn't surfaced to the user.
		const longName = `POS-647 ${ 'A'.repeat( 250 ) }`; // 258 chars total
		const product = await createProduct( requestUtils, {
			name: longName,
			regular_price: '10.00',
		} );

		try {
			await syncProduct( cli, product.id );
			await wcProducts.visit();
			await wcProducts.assertProductSyncStatus(
				longName,
				'not-synced', // generic status — see POS-649 for the underlying gap
				product.id
			);
		} finally {
			await deleteProduct( cli, product.id );
		}
	} );

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

		const PRODUCT_NAME = 'POS-650 No Tax Rate';

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
			name: PRODUCT_NAME,
			regular_price: '15.00',
			tax_class: taxClass.slug,
		} );

		try {
			await syncProduct( cli, product.id );
			await wcProducts.visit();
			await wcProducts.assertProductSyncStatus(
				PRODUCT_NAME,
				'no-tax-rate',
				product.id
			);

			const products = ( await zettleApi.getProducts() ) as ZettleProduct[];
			const foundInPos = products.find(
				( p ) => p.name === PRODUCT_NAME
			);
			expect(
				foundInPos,
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

			const remoteProducts =
				( await zettleApi.getProducts() ) as ZettleProduct[];
			const leftover = remoteProducts.find(
				( p ) => p.name === PRODUCT_NAME
			);
			if ( leftover?.uuid ) {
				await zettleApi.deleteProduct( leftover.uuid );
			}
		}
	} );
} );
