import { test as setup } from '../../utils';
import { resetOnboarding } from '../../utils';
import { e2ePlugins } from '../../resources';

setup( 'Connect PayPal POS', async ( { posSettings, requestUtils, cli } ) => {
    const apiKey = process.env.PAYPAL_POS_API_KEY;

    await requestUtils.activatePlugin( e2ePlugins.paypalPos.slug );
    await resetOnboarding( cli );

    if ( apiKey ) {
        await posSettings.connect( apiKey, cli );
    }
} );
