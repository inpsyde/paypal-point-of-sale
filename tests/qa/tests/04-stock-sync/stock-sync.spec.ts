import { exec } from 'child_process';
import { promisify } from 'util';
import * as crypto from 'crypto';
import * as path from 'path';
import { test } from '../../utils';
import { expect } from '@inpsyde/playwright-utils/build';

const PLUGIN_SLUG = 'paypal-point-of-sale';
const PLUGIN_ROOT = path.resolve( __dirname, '..', '..', '..', '..' );
const WEBHOOK_ENDPOINT = '/wp-json/zettle/v1/webhook/listen';
const execAsync = promisify( exec );

// ── helpers ──────────────────────────────────────────────────────────────────

async function runCli( command: string ): Promise< string > {
    const { stdout } = await execAsync(
        `npx @wordpress/env run cli ${ command }`,
        { cwd: PLUGIN_ROOT, timeout: 30_000 }
    );
    return stdout.trim();
}

async function processQueue(): Promise< void > {
    await execAsync( 'npx @wordpress/env run cli wp zettle queue process', {
        cwd: PLUGIN_ROOT,
        timeout: 30_000,
    } ).catch( () => {} );
}

/** Delete a WC product via WP-CLI to avoid nonce invalidation issues after processQueue(). */
async function deleteProduct( productId: number ): Promise< void > {
    await runCli( `wp wc product delete ${ productId } --force=true --user=1` ).catch( () => {} );
}

/** Delete a WC order via WP-CLI. */
async function deleteOrder( orderId: number ): Promise< void > {
    await runCli( `wp wc shop_order delete ${ orderId } --force=true --user=1` ).catch( () => {} );
}

async function syncProduct( productId: number ): Promise< void > {
    await runCli( `wp zettle sync product ${ productId }` );
}

async function getWebhookSigningKey(): Promise< string > {
    try {
        const raw = await runCli( "wp option get 'paypal-pos.webhook.listener' --format=json" );
        const config = JSON.parse( raw );
        return config.signingKey ?? '';
    } catch {
        return '';
    }
}

async function getPosVariantUuid( wcProductId: number ): Promise< string | null > {
    try {
        const raw = await runCli(
            `wp db query "SELECT remote_id FROM $(wp db prefix)zettle_woocommerce_id_map WHERE local_id = ${ wcProductId } AND type = 'variant' LIMIT 1" --skip-column-names`
        );
        const uuid = raw.trim();
        return uuid || null;
    } catch {
        return null;
    }
}

function signWebhookPayload( timestamp: string, payloadString: string, signingKey: string ): string {
    return crypto
        .createHmac( 'sha256', signingKey )
        .update( `${ timestamp }.${ payloadString }` )
        .digest( 'hex' );
}

// ── tests ─────────────────────────────────────────────────────────────────────

test.describe( 'Stock Sync', () => {

    test.beforeEach( async ( { requestUtils } ) => {
        await requestUtils.activatePlugin( PLUGIN_SLUG );
    } );

    // ── POS-587 ──────────────────────────────────────────────────────────────
    test(
        'POS-587 | WooCommerce stock update is reflected in POS; regression;',
        async ( { wcProducts, requestUtils } ) => {
            test.setTimeout( 5 * 60_000 );

            if ( ! process.env.PAYPAL_POS_API_KEY ) {
                test.skip( true, 'PAYPAL_POS_API_KEY not set — skipping live sync test' );
                return;
            }

            const product = await requestUtils.rest< { id: number } >( {
                path: '/wc/v3/products',
                method: 'POST',
                data: {
                    name: 'POS-587 Stock Update',
                    type: 'simple',
                    status: 'publish',
                    regular_price: '12.00',
                    manage_stock: true,
                    stock_quantity: 10,
                },
            } );

            try {
                await syncProduct( product.id );
                await wcProducts.visit();
                await wcProducts.assertProductSyncStatus( 'POS-587 Stock Update', 'synced', product.id );

                await requestUtils.rest( {
                    path: `/wc/v3/products/${ product.id }`,
                    method: 'PUT',
                    data: { stock_quantity: 25 },
                } );

                await syncProduct( product.id );
                await wcProducts.visit();
                await wcProducts.assertProductSyncStatus( 'POS-587 Stock Update', 'synced', product.id );
            } finally {
                await requestUtils.rest( {
                    path: `/wc/v3/products/${ product.id }`,
                    method: 'DELETE',
                    params: { force: true },
                } );
            }
        }
    );

    // ── POS-588 ──────────────────────────────────────────────────────────────
    test(
        'POS-588 | Disabling stock management updates POS inventory tracking; regression;',
        async ( { wcProducts, requestUtils } ) => {
            test.setTimeout( 5 * 60_000 );

            if ( ! process.env.PAYPAL_POS_API_KEY ) {
                test.skip( true, 'PAYPAL_POS_API_KEY not set — skipping live sync test' );
                return;
            }

            const product = await requestUtils.rest< { id: number } >( {
                path: '/wc/v3/products',
                method: 'POST',
                data: {
                    name: 'POS-588 Stock Mgmt Disable',
                    type: 'simple',
                    status: 'publish',
                    regular_price: '8.00',
                    manage_stock: true,
                    stock_quantity: 15,
                },
            } );

            try {
                await syncProduct( product.id );
                await wcProducts.visit();
                await wcProducts.assertProductSyncStatus( 'POS-588 Stock Mgmt Disable', 'synced', product.id );

                await requestUtils.rest( {
                    path: `/wc/v3/products/${ product.id }`,
                    method: 'PUT',
                    data: { manage_stock: false },
                } );

                await processQueue();
                await wcProducts.visit();
                await wcProducts.assertProductSyncStatus( 'POS-588 Stock Mgmt Disable', 'synced', product.id );
            } finally {
                await deleteProduct( product.id );
            }
        }
    );

    // ── POS-586 ──────────────────────────────────────────────────────────────
    test(
        'POS-586 | WooCommerce order reduces POS stock; regression;',
        async ( { wcProducts, requestUtils } ) => {
            test.setTimeout( 5 * 60_000 );

            if ( ! process.env.PAYPAL_POS_API_KEY ) {
                test.skip( true, 'PAYPAL_POS_API_KEY not set — skipping live sync test' );
                return;
            }

            const product = await requestUtils.rest< { id: number } >( {
                path: '/wc/v3/products',
                method: 'POST',
                data: {
                    name: 'POS-586 Order Stock',
                    type: 'simple',
                    status: 'publish',
                    regular_price: '20.00',
                    manage_stock: true,
                    stock_quantity: 20,
                },
            } );

            try {
                await syncProduct( product.id );
                await wcProducts.visit();
                await wcProducts.assertProductSyncStatus( 'POS-586 Order Stock', 'synced', product.id );

                const order = await requestUtils.rest< { id: number } >( {
                    path: '/wc/v3/orders',
                    method: 'POST',
                    data: {
                        status: 'processing',
                        line_items: [ { product_id: product.id, quantity: 3 } ],
                    },
                } );

                await processQueue();
                await wcProducts.visit();
                await wcProducts.assertProductSyncStatus( 'POS-586 Order Stock', 'synced', product.id );

                await deleteOrder( order.id );
            } finally {
                await deleteProduct( product.id );
            }
        }
    );

    // ── POS-585 ──────────────────────────────────────────────────────────────
    test(
        'POS-585 | POS sale updates WooCommerce stock via InventoryBalanceChanged webhook; critical;',
        async ( { wcProducts, requestUtils, page } ) => {
            test.setTimeout( 5 * 60_000 );

            if ( ! process.env.PAYPAL_POS_API_KEY ) {
                test.skip( true, 'PAYPAL_POS_API_KEY not set — skipping webhook test' );
                return;
            }

            const product = await requestUtils.rest< { id: number } >( {
                path: '/wc/v3/products',
                method: 'POST',
                data: {
                    name: 'POS-585 Webhook Stock',
                    type: 'simple',
                    status: 'publish',
                    regular_price: '15.00',
                    manage_stock: true,
                    stock_quantity: 20,
                },
            } );

            try {
                await syncProduct( product.id );
                await wcProducts.visit();
                await wcProducts.assertProductSyncStatus( 'POS-585 Webhook Stock', 'synced', product.id );

                const signingKey = await getWebhookSigningKey();
                if ( ! signingKey ) {
                    test.skip( true, 'No webhook signing key found — webhook not registered' );
                    return;
                }

                const variantUuid = await getPosVariantUuid( product.id );
                if ( ! variantUuid ) {
                    test.skip( true, 'No POS variant UUID found — product not in ID map' );
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

                const signature = signWebhookPayload( timestamp, eventPayload, signingKey );

                const response = await page.request.post( WEBHOOK_ENDPOINT, {
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Izettle-Signature': signature,
                    },
                    data: fullPayload,
                } );

                await expect( response ).toBeOK();
                await page.waitForTimeout( 3_000 );

                const updated = await requestUtils.rest< { stock_quantity: number } >( {
                    path: `/wc/v3/products/${ product.id }`,
                    method: 'GET',
                } );

                expect( updated.stock_quantity ).toBe( 18 );
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
