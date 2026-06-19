import { test } from '../../utils';
import { processQueue, syncProduct } from '../../utils';

const PLUGIN_SLUG = 'paypal-point-of-sale';

// ── tests ─────────────────────────────────────────────────────────────────────

test.describe( 'Product Sync (WC → POS)', () => {

    test.beforeEach( async ( { requestUtils } ) => {
        await requestUtils.activatePlugin( PLUGIN_SLUG );
    } );

    // ── POS-579 ──────────────────────────────────────────────────────────────
    test(
        'POS-579 | Sync status column appears in product list; regression;',
        async ( { wcProducts, requestUtils } ) => {
            const product = await requestUtils.rest< { id: number } >( {
                path: '/wc/v3/products',
                method: 'POST',
                data: { name: 'POS-579 Column Test', type: 'simple', status: 'publish', regular_price: '5.00' },
            } );

            try {
                await wcProducts.assertSyncStatusColumnVisible();
            } finally {
                await requestUtils.rest( {
                    path: `/wc/v3/products/${ product.id }`,
                    method: 'DELETE',
                    params: { force: true },
                } );
            }
        }
    );

    // ── POS-573 ──────────────────────────────────────────────────────────────
    test(
        'POS-573 | Draft product is not synced to POS; regression;',
        async ( { wcProducts, requestUtils } ) => {
            const product = await requestUtils.rest< { id: number } >( {
                path: '/wc/v3/products',
                method: 'POST',
                data: { name: 'POS-573 Draft Product', type: 'simple', status: 'draft', regular_price: '9.99' },
            } );

            try {
                await wcProducts.visit( 'draft' );
                await wcProducts.assertProductSyncStatus( 'POS-573 Draft Product', 'not-published', product.id );
            } finally {
                await requestUtils.rest( {
                    path: `/wc/v3/products/${ product.id }`,
                    method: 'DELETE',
                    params: { force: true },
                } );
            }
        }
    );

    // ── POS-581 ──────────────────────────────────────────────────────────────
    test(
        'POS-581 | Simple product created syncs to POS; critical;',
        async ( { wcProducts, requestUtils, cli } ) => {
            test.setTimeout( 5 * 60_000 );

            if ( ! process.env.PAYPAL_POS_API_KEY ) {
                test.skip( true, 'PAYPAL_POS_API_KEY not set — skipping live sync test' );
                return;
            }

            const product = await requestUtils.rest< { id: number } >( {
                path: '/wc/v3/products',
                method: 'POST',
                data: {
                    name: 'POS-581 Simple Product',
                    type: 'simple',
                    status: 'publish',
                    regular_price: '19.99',
                    manage_stock: true,
                    stock_quantity: 10,
                },
            } );

            try {
                await syncProduct( cli, product.id );
                await wcProducts.visit();
                await wcProducts.assertProductSyncStatus( 'POS-581 Simple Product', 'synced', product.id );
            } finally {
                await requestUtils.rest( {
                    path: `/wc/v3/products/${ product.id }`,
                    method: 'DELETE',
                    params: { force: true },
                } );
            }
        }
    );

    // ── POS-582 ──────────────────────────────────────────────────────────────
    test(
        'POS-582 | Simple product deleted is removed from POS; regression;',
        async ( { wcProducts, requestUtils, cli } ) => {
            test.setTimeout( 5 * 60_000 );

            if ( ! process.env.PAYPAL_POS_API_KEY ) {
                test.skip( true, 'PAYPAL_POS_API_KEY not set — skipping live sync test' );
                return;
            }

            const product = await requestUtils.rest< { id: number } >( {
                path: '/wc/v3/products',
                method: 'POST',
                data: { name: 'POS-582 Delete Me', type: 'simple', status: 'publish', regular_price: '5.00' },
            } );

            await syncProduct( cli, product.id );
            await wcProducts.visit();
            await wcProducts.assertProductSyncStatus( 'POS-582 Delete Me', 'synced', product.id );

            await requestUtils.rest( {
                path: `/wc/v3/products/${ product.id }`,
                method: 'DELETE',
                params: { force: false },
            } );

            await processQueue( cli );

            await wcProducts.visit( 'trash' );
            await wcProducts.assertProductSyncStatus( 'POS-582 Delete Me', 'not-synced', product.id );
        }
    );

    // ── POS-583 ──────────────────────────────────────────────────────────────
    test(
        'POS-583 | Simple product name and price update syncs to POS; regression;',
        async ( { wcProducts, requestUtils, cli } ) => {
            test.setTimeout( 5 * 60_000 );

            if ( ! process.env.PAYPAL_POS_API_KEY ) {
                test.skip( true, 'PAYPAL_POS_API_KEY not set — skipping live sync test' );
                return;
            }

            const product = await requestUtils.rest< { id: number } >( {
                path: '/wc/v3/products',
                method: 'POST',
                data: { name: 'POS-583 Original Name', type: 'simple', status: 'publish', regular_price: '10.00' },
            } );

            try {
                await syncProduct( cli, product.id );
                await wcProducts.visit();
                await wcProducts.assertProductSyncStatus( 'POS-583 Original Name', 'synced', product.id );

                await requestUtils.rest( {
                    path: `/wc/v3/products/${ product.id }`,
                    method: 'PUT',
                    data: { name: 'POS-583 Updated Name', regular_price: '29.99' },
                } );

                await syncProduct( cli, product.id );
                await wcProducts.visit();
                await wcProducts.assertProductSyncStatus( 'POS-583 Updated Name', 'synced', product.id );
            } finally {
                await requestUtils.rest( {
                    path: `/wc/v3/products/${ product.id }`,
                    method: 'DELETE',
                    params: { force: true },
                } );
            }
        }
    );

    // ── POS-578 ──────────────────────────────────────────────────────────────
    test(
        'POS-578 | Excluded product is removed from POS and shows Excluded status; regression;',
        async ( { wcProducts, wcProductEdit, requestUtils, cli } ) => {
            test.setTimeout( 5 * 60_000 );

            if ( ! process.env.PAYPAL_POS_API_KEY ) {
                test.skip( true, 'PAYPAL_POS_API_KEY not set — skipping live sync test' );
                return;
            }

            const product = await requestUtils.rest< { id: number } >( {
                path: '/wc/v3/products',
                method: 'POST',
                data: { name: 'POS-578 Exclude Me', type: 'simple', status: 'publish', regular_price: '15.00' },
            } );

            try {
                await syncProduct( cli, product.id );
                await wcProducts.visit();
                await wcProducts.assertProductSyncStatus( 'POS-578 Exclude Me', 'synced', product.id );

                await wcProductEdit.visitExisting( product.id );
                await wcProductEdit.setExcludeFromSync( true );
                await wcProductEdit.update();

                await processQueue( cli );

                await wcProducts.visit();
                await wcProducts.assertProductSyncStatus( 'POS-578 Exclude Me', 'excluded', product.id );
            } finally {
                await requestUtils.rest( {
                    path: `/wc/v3/products/${ product.id }`,
                    method: 'DELETE',
                    params: { force: true },
                } );
            }
        }
    );

    // ── POS-580 ──────────────────────────────────────────────────────────────
    test(
        'POS-580 | Product type changed simple to variable re-syncs to POS; regression;',
        async ( { wcProducts, requestUtils, cli } ) => {
            test.setTimeout( 5 * 60_000 );

            if ( ! process.env.PAYPAL_POS_API_KEY ) {
                test.skip( true, 'PAYPAL_POS_API_KEY not set — skipping live sync test' );
                return;
            }

            const product = await requestUtils.rest< { id: number } >( {
                path: '/wc/v3/products',
                method: 'POST',
                data: { name: 'POS-580 Type Change', type: 'simple', status: 'publish', regular_price: '20.00' },
            } );

            try {
                await syncProduct( cli, product.id );
                await wcProducts.visit();
                await wcProducts.assertProductSyncStatus( 'POS-580 Type Change', 'synced', product.id );

                await requestUtils.rest( {
                    path: `/wc/v3/products/${ product.id }`,
                    method: 'PUT',
                    data: {
                        type: 'variable',
                        attributes: [ {
                            name: 'Color',
                            variation: true,
                            visible: true,
                            options: [ 'Red', 'Blue' ],
                        } ],
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
                await wcProducts.assertProductSyncStatus( 'POS-580 Type Change', 'synced', product.id );
            } finally {
                await requestUtils.rest( {
                    path: `/wc/v3/products/${ product.id }`,
                    method: 'DELETE',
                    params: { force: true },
                } );
            }
        }
    );

    // ── POS-584 ──────────────────────────────────────────────────────────────
    test(
        'POS-584 | Variable product full lifecycle — create, add variation, delete variation; regression;',
        async ( { wcProducts, requestUtils, cli } ) => {
            test.setTimeout( 10 * 60_000 );

            if ( ! process.env.PAYPAL_POS_API_KEY ) {
                test.skip( true, 'PAYPAL_POS_API_KEY not set — skipping live sync test' );
                return;
            }

            const product = await requestUtils.rest< { id: number } >( {
                path: '/wc/v3/products',
                method: 'POST',
                data: {
                    name: 'POS-584 T-Shirt',
                    type: 'variable',
                    status: 'publish',
                    attributes: [ {
                        name: 'Size',
                        variation: true,
                        visible: true,
                        options: [ 'S', 'L', 'XL' ],
                    } ],
                },
            } );

            try {
                const variationS = await requestUtils.rest< { id: number } >( {
                    path: `/wc/v3/products/${ product.id }/variations`,
                    method: 'POST',
                    data: { attributes: [ { name: 'Size', option: 'S' } ], regular_price: '15.00', manage_stock: true, stock_quantity: 20 },
                } );

                const variationL = await requestUtils.rest< { id: number } >( {
                    path: `/wc/v3/products/${ product.id }/variations`,
                    method: 'POST',
                    data: { attributes: [ { name: 'Size', option: 'L' } ], regular_price: '17.00', manage_stock: true, stock_quantity: 15 },
                } );

                await syncProduct( cli, product.id );
                await wcProducts.visit();
                await wcProducts.assertProductSyncStatus( 'POS-584 T-Shirt', 'synced', product.id );

                const variationXL = await requestUtils.rest< { id: number } >( {
                    path: `/wc/v3/products/${ product.id }/variations`,
                    method: 'POST',
                    data: { attributes: [ { name: 'Size', option: 'XL' } ], regular_price: '19.00', manage_stock: true, stock_quantity: 10 },
                } );

                await syncProduct( cli, product.id );
                await wcProducts.visit();
                await wcProducts.assertProductSyncStatus( 'POS-584 T-Shirt', 'synced', product.id );

                await requestUtils.rest( {
                    path: `/wc/v3/products/${ product.id }/variations/${ variationXL.id }`,
                    method: 'DELETE',
                    params: { force: true },
                } );

                await processQueue( cli );
                await wcProducts.visit();
                await wcProducts.assertProductSyncStatus( 'POS-584 T-Shirt', 'synced', product.id );

                await requestUtils.rest( { path: `/wc/v3/products/${ product.id }/variations/${ variationS.id }`, method: 'DELETE', params: { force: true } } );
                await requestUtils.rest( { path: `/wc/v3/products/${ product.id }/variations/${ variationL.id }`, method: 'DELETE', params: { force: true } } );
            } finally {
                await requestUtils.rest( {
                    path: `/wc/v3/products/${ product.id }`,
                    method: 'DELETE',
                    params: { force: true },
                } );
            }
        }
    );

} );
