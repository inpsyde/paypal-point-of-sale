import { exec } from 'child_process';
import { promisify } from 'util';
import * as path from 'path';
import { test, PosSettingsPage } from '../../utils';
import { expect } from '@inpsyde/playwright-utils/build';

const PLUGIN_SLUG = 'paypal-point-of-sale';
const PLUGIN_ROOT = path.resolve( __dirname, '..', '..', '..', '..' );
const execAsync = promisify( exec );

async function resetOnboardingState(): Promise<void> {
    await execAsync( 'npx @wordpress/env run cli wp zettle reset onboarding complete', {
        cwd: PLUGIN_ROOT,
        timeout: 60_000,
    } );
}

async function connectWithApiKey( posSettings: PosSettingsPage, apiKey: string ) {
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
            await execAsync( 'npx @wordpress/env run cli wp zettle queue process', {
                cwd: PLUGIN_ROOT,
                timeout: 30_000,
            } ).catch( () => {} );
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

test.describe( 'Plugin Lifecycle', () => {

    test.beforeEach( async ( { requestUtils } ) => {
        await requestUtils.activatePlugin( PLUGIN_SLUG );
        await resetOnboardingState();
    } );

    test(
        'POS-565 | Plugin installation & activation - happy path; smoke; critical;',
        async ( { page, posSettings, requestUtils } ) => {
            // Assert plugin is listed as active (plugin already installed in wp-env)
            const pluginsList = await requestUtils.rest< { name: string; status: string }[] >( {
                path: '/wp/v2/plugins',
                method: 'GET',
            } );
            const plugin = pluginsList.find( ( p ) => p.name?.includes( 'PayPal Point of Sale' ) );
            expect( plugin?.status ).toBe( 'active' );

            // POS tab appears in WooCommerce Settings
            await posSettings.visit();
            await posSettings.assertTabVisible();

            // Welcome screen is shown on the POS settings page
            await posSettings.assertWelcomeState();

            // WC Status report shows a PayPal POS section
            await page.goto( '/wp-admin/admin.php?page=wc-status' );
            await expect(
                page.getByRole( 'heading', { name: 'PayPal Point of Sale', level: 2 } )
            ).toBeVisible();

            // No JS errors on the POS settings page
            const jsErrors: string[] = [];
            page.on( 'pageerror', ( err ) => jsErrors.push( err.message ) );
            await posSettings.visit();
            await page.waitForLoadState( 'load' );
            expect( jsErrors ).toHaveLength( 0 );
        }
    );

    test(
        'POS-568 | Upgrade - settings and sync state preserved; regression;',
        async ( { posSettings, requestUtils } ) => {
            test.setTimeout( 10 * 60_000 );

            const apiKey = process.env.PAYPAL_POS_API_KEY;
            if ( ! apiKey ) {
                test.skip( true, 'PAYPAL_POS_API_KEY not set — skipping upgrade test' );
                return;
            }

            await resetOnboardingState();

            // Establish a connected state
            await connectWithApiKey( posSettings, apiKey );

            // Simulate upgrade lifecycle: deactivate → reactivate
            // (An upgrade triggers activation hooks without uninstall — options are preserved)
            await requestUtils.deactivatePlugin( PLUGIN_SLUG );
            await requestUtils.activatePlugin( PLUGIN_SLUG );

            // Settings must be preserved after the plugin lifecycle cycle
            await posSettings.visit();
            await posSettings.assertConnectedState();

            // POS tab still present in WC Settings
            await posSettings.assertTabVisible();
        }
    );

} );
