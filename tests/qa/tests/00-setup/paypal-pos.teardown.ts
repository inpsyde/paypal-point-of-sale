import {
	test as teardown,
	resetOnboarding,
	ensurePluginState,
} from '../../utils';
import { e2ePlugins } from '../../resources';

teardown(
	'Reset PayPal POS state',
	async ( { requestUtils, plugins, cli } ) => {
		await ensurePluginState( requestUtils, plugins, e2ePlugins.paypalPos );
		await resetOnboarding( cli );
	}
);
