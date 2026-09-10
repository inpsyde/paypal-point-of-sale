import { restLogin } from '@inpsyde/playwright-utils/build';
import {
	test as setup,
	setupSiteVisibility,
	ensureWooCommerceApiKeys,
	setupGeneralSettings,
	setupTaxes,
} from '../../utils';

setup( 'Setup: Refresh admin session', async () => {
	await restLogin( {
		baseURL: process.env.WP_BASE_URL as string,
		storageStatePath: process.env.STORAGE_STATE_PATH_ADMIN as string,
		user: {
			username: process.env.WP_USERNAME as string,
			password: process.env.WP_PASSWORD as string,
		},
	} );
} );

setup( 'Setup: Permalinks', async ( { requestUtils } ) => {
	await requestUtils.setPermalinks( '/%postname%/' );
} );

setup( 'Setup: WooCommerce site visibility', async ( { wooCommerceUtils } ) => {
	await setupSiteVisibility( wooCommerceUtils );
} );

setup(
	'Setup: WooCommerce API keys',
	async ( { wooCommerceUtils, wooCommerceApi } ) => {
		await ensureWooCommerceApiKeys( wooCommerceUtils, wooCommerceApi );
	}
);

setup( 'Setup: WooCommerce general settings', async ( { wooCommerceApi } ) => {
	await setupGeneralSettings( wooCommerceApi );
} );

setup( 'Setup: WooCommerce taxes', async ( { wooCommerceUtils } ) => {
	await setupTaxes( wooCommerceUtils );
} );
