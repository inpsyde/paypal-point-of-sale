import { test as setup } from '../../utils';
import { resetEnvironment } from '../../utils';

// Destructive: wipes the target database and reinstalls WP + WooCommerce + Storefront.
// Requires an explicit E2E_CONFIRM_RESET=true so it can't run by accident
// via an unfiltered `playwright test`, especially against a shared host like Kinsta.
setup( 'Setup: Reset Environment', async ( { cli } ) => {
    setup.skip(
        process.env.E2E_CONFIRM_RESET !== 'true',
        'Set E2E_CONFIRM_RESET=true to run this destructive reset.'
    );

    await resetEnvironment( cli );
} );
