import { expect } from '@inpsyde/playwright-utils/build';
import { test, ensurePluginState, resetOnboarding, ZettleApiClient } from '../../utils';
import { e2ePlugins } from '../../resources';

const WEBHOOK_ENDPOINT = '/wp-json/zettle/v1/webhook/listen';
const CLIENT_ID = 'de149dc7-44b5-4390-ab64-88e301771f06';

// ── tests ─────────────────────────────────────────────────────────────────────

test.describe( 'Webhooks', () => {

    test.beforeEach( async ( { requestUtils, plugins, cli } ) => {
        await ensurePluginState( requestUtils, plugins, e2ePlugins.paypalPos );
        await resetOnboarding( cli );
    } );

    // ── POS-591 ──────────────────────────────────────────────────────────────
    test(
        'POS-591 | Registered on Connect; critical;',
        async ( { posSettings, cli, request } ) => {
            test.setTimeout( 5 * 60_000 );

            const apiKey = process.env.PAYPAL_POS_API_KEY;
            if ( ! apiKey ) {
                test.skip( true, 'PAYPAL_POS_API_KEY not set — skipping live webhook registration test' );
                return;
            }

            // PayPal connects to the destination to validate it during registration —
            // localhost is never reachable from their servers (DESTINATION_NOT_ACCESSIBLE).
            const isPubliclyReachable = process.env.NGROK_ENABLED === 'true'
                || ! /localhost|127\.0\.0\.1/.test( process.env.WP_BASE_URL ?? '' );
            if ( ! isPubliclyReachable ) {
                test.skip( true, 'WP_BASE_URL is not publicly reachable (no ngrok) — webhook registration cannot succeed' );
                return;
            }

            await posSettings.connect( apiKey, cli );

            const zettleApi = new ZettleApiClient( request );
            await zettleApi.authenticate( CLIENT_ID, apiKey );
            const subscriptions = await zettleApi.getWebhookSubscriptions();

            // Match on host + path, not just a path suffix — otherwise a stale subscription
            // left over from a different environment (e.g. Kinsta) would false-positive here.
            const expectedHost = new URL( process.env.WP_BASE_URL ?? '' ).host;
            const registered = subscriptions.find( ( subscription ) => {
                const destination = new URL( subscription.destination );
                return destination.host === expectedHost
                    && destination.pathname === WEBHOOK_ENDPOINT
                    && subscription.eventNames.includes( 'InventoryBalanceChanged' );
            } );
            expect( registered, 'no InventoryBalanceChanged webhook registered for this site on PayPal POS' ).toBeTruthy();
        }
    );
} );
