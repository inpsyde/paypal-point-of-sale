import { test, PosSettingsPage } from '../../utils';
import { resetOnboarding, processQueue, AnyCli } from '../../utils';

const PLUGIN_SLUG = 'paypal-point-of-sale';
// Well-formed JWT (passes format validation) but invalid credentials (fails PayPal auth).
const INVALID_API_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJ0ZXN0LWludmFsaWQiLCJpc3MiOiJwYXlwYWwiLCJpYXQiOjE3MDAwMDAwMDB9.SIG_INVALID_FAKE_DO_NOT_USE';

async function connectWithApiKey( posSettings: PosSettingsPage, apiKey: string, cli: AnyCli ): Promise< void > {
    await posSettings.visit();
    await posSettings.assertWelcomeState();
    await posSettings.clickConnect();
    await posSettings.assertApiCredentialsState();
    await posSettings.enterApiKey( apiKey );
    await posSettings.clickAuthenticate();
    await posSettings.mergeRadio().check();
    await posSettings.clickNext();
    await posSettings.clickStartSync();

    // WP-Cron and AsyncRequestRunner rely on HTTP loopback which doesn't work
    // inside the Docker wp-env container. Drive the queue processor via WP-CLI
    // concurrently while the JS polling waits for the sync to finish.
    let syncComplete = false;
    void ( async () => {
        while ( ! syncComplete ) {
            await processQueue( cli );
        }
    } )();

    try {
        // The cancel button detaches when JS auto-proceeds to SYNC_FINISHED.
        await posSettings.page.waitForSelector(
            'button[name="cancel"]', { state: 'detached', timeout: 180_000 }
        );
    } finally {
        syncComplete = true;
    }

    // Now on SYNC_FINISHED page — click "Complete setup" to reach ONBOARDING_COMPLETED.
    await posSettings.page.waitForLoadState( 'load' );
    await posSettings.page.getByRole( 'button', { name: 'Complete setup' } ).click();
    await posSettings.page.waitForLoadState( 'load' );
    await posSettings.assertConnectedState();
}

async function disconnectAndConfirm( posSettings: PosSettingsPage ): Promise< void > {
    await posSettings.disconnectTrigger().click();
    await posSettings.disconnectModalHeading().waitFor();
    await posSettings.disconnectConfirm().click();
    await posSettings.page.waitForLoadState( 'load' );
}

test.describe( 'Onboarding', () => {

    test.beforeEach( async ( { requestUtils, cli } ) => {
        await requestUtils.activatePlugin( PLUGIN_SLUG );
        await resetOnboarding( cli );
    } );

    test( 'POS-570 | Onboarding happy path — full connect with valid API key; smoke; critical;',
        async ( { posSettings, cli } ) => {
            test.setTimeout( 5 * 60_000 );

            const apiKey = process.env.PAYPAL_POS_API_KEY;
            if ( ! apiKey ) {
                test.skip( true, 'PAYPAL_POS_API_KEY not set — skipping live onboarding test' );
                return;
            }

            await connectWithApiKey( posSettings, apiKey, cli );
        } );

    test( 'POS-571 | Invalid API key shows Authentication failed screen; critical;',
        async ( { posSettings } ) => {
            await posSettings.visit();
            await posSettings.assertWelcomeState();

            await posSettings.clickConnect();
            await posSettings.assertApiCredentialsState();

            await posSettings.enterApiKey( INVALID_API_KEY );
            await posSettings.clickAuthenticate();

            await posSettings.assertInvalidCredentialsState();
        } );

    test( 'POS-571 | Start over after invalid key returns to API credentials screen; regression;',
        async ( { posSettings } ) => {
            await posSettings.visit();
            await posSettings.clickConnect();
            await posSettings.enterApiKey( INVALID_API_KEY );
            await posSettings.clickAuthenticate();
            await posSettings.assertInvalidCredentialsState();

            await posSettings.clickStartOver();
            await posSettings.assertApiCredentialsState();
        } );

    test( 'POS-572 | Reconnect after disconnect restores connected state; regression;',
        async ( { posSettings, cli } ) => {
            test.setTimeout( 10 * 60_000 );

            const apiKey = process.env.PAYPAL_POS_API_KEY;
            if ( ! apiKey ) {
                test.skip( true, 'PAYPAL_POS_API_KEY not set — skipping reconnect test' );
                return;
            }

            await connectWithApiKey( posSettings, apiKey, cli );
            await disconnectAndConfirm( posSettings );
            await posSettings.assertWelcomeState();

            await connectWithApiKey( posSettings, apiKey, cli );
        } );

    test( 'POS-577 | Disconnect removes connected state and returns to welcome screen; critical;',
        async ( { posSettings, cli } ) => {
            test.setTimeout( 5 * 60_000 );

            const apiKey = process.env.PAYPAL_POS_API_KEY;
            if ( ! apiKey ) {
                test.skip( true, 'PAYPAL_POS_API_KEY not set — skipping disconnect test' );
                return;
            }

            await connectWithApiKey( posSettings, apiKey, cli );
            await disconnectAndConfirm( posSettings );
            await posSettings.assertWelcomeState();
        } );

} );
