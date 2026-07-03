import { test as setup } from '../../utils';
import { resetOnboarding, ensurePluginState } from '../../utils';
import { e2ePlugins } from '../../resources';

setup( 'Connect PayPal POS', async ( { posSettings, requestUtils, plugins, cli } ) => {
    const apiKey = process.env.PAYPAL_POS_API_KEY;

    await ensurePluginState( requestUtils, plugins, e2ePlugins.paypalPos );
    await resetOnboarding( cli );

    if ( apiKey ) {
        await posSettings.connect( apiKey, cli );
    }
} );
