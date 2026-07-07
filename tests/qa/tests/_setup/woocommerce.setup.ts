import { restLogin } from '@inpsyde/playwright-utils/build';
import { test as setup } from '../../utils';
import {
    setupSiteVisibility,
    ensureWooCommerceApiKeys,
    setupGeneralSettings,
    setupTaxes,
} from '../../utils';

// env.setup.ts's reset recreates the database, which invalidates whatever session
// storage-states/admin.json held. Refresh it before any step below runs — they all use
// fixtures (requestUtils, wooCommerceUtils, wooCommerceApi) that read that file.
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

setup( 'Setup: WooCommerce API keys', async ( { wooCommerceUtils } ) => {
    await ensureWooCommerceApiKeys( wooCommerceUtils );
} );

setup( 'Setup: WooCommerce general settings', async ( { wooCommerceApi } ) => {
    await setupGeneralSettings( wooCommerceApi );
} );

setup( 'Setup: WooCommerce taxes', async ( { wooCommerceUtils } ) => {
    await setupTaxes( wooCommerceUtils );
} );
