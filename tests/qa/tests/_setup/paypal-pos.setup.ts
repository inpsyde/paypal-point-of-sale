import { test as setup, PosSettingsPage } from '../../utils';
import { resetOnboarding, processQueue, AnyCli } from '../../utils';

const PLUGIN_SLUG = 'paypal-point-of-sale';

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

    let syncComplete = false;
    void ( async () => {
        while ( ! syncComplete ) {
            await processQueue( cli );
        }
    } )();

    try {
        await posSettings.page.waitForSelector(
            'button[name="cancel"]', { state: 'detached', timeout: 180_000 }
        );
    } finally {
        syncComplete = true;
    }

    await posSettings.page.waitForLoadState( 'load' );
    await posSettings.page.getByRole( 'button', { name: 'Complete setup' } ).click();
    await posSettings.page.waitForLoadState( 'load' );
    await posSettings.assertConnectedState();
}

setup( 'Connect PayPal POS', async ( { posSettings, requestUtils, cli } ) => {
    const apiKey = process.env.PAYPAL_POS_API_KEY;
    if ( ! apiKey ) {
        // No API key — reset to disconnected state so tests skip cleanly
        await requestUtils.activatePlugin( PLUGIN_SLUG );
        await resetOnboarding( cli );
        return;
    }

    await requestUtils.activatePlugin( PLUGIN_SLUG );
    await resetOnboarding( cli );
    await connectWithApiKey( posSettings, apiKey, cli );
} );
