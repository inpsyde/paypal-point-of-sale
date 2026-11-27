import {
	test as setup,
	resetOnboarding,
	ensurePluginState,
	requireEnv,
} from '../../utils';
import { e2ePlugins } from '../../resources';

setup(
	'Connect PayPal POS',
	async ( { posSettings, requestUtils, plugins, cli } ) => {
		const apiKey = requireEnv( 'PAYPAL_POS_API_KEY' );

		await ensurePluginState( requestUtils, plugins, e2ePlugins.paypalPos );
		await resetOnboarding( cli );

		await posSettings.connect( apiKey, cli );
	}
);
