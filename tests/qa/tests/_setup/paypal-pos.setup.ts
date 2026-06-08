import { exec } from 'child_process';
import { promisify } from 'util';
import * as path from 'path';
import { test as setup, PosSettingsPage } from '../../utils';

const PLUGIN_SLUG = 'paypal-point-of-sale';
const PLUGIN_ROOT = path.resolve( __dirname, '..', '..', '..', '..' );
const execAsync = promisify( exec );

async function resetOnboardingState(): Promise< void > {
    await execAsync( 'npx @wordpress/env run cli wp zettle reset onboarding complete', {
        cwd: PLUGIN_ROOT,
        timeout: 60_000,
    } );
}

async function processQueue(): Promise< void > {
    await execAsync( 'npx @wordpress/env run cli wp zettle queue process', {
        cwd: PLUGIN_ROOT,
        timeout: 30_000,
    } ).catch( () => {} );
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
            await processQueue();
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

setup( 'Connect PayPal POS', async ( { posSettings, requestUtils } ) => {
    const apiKey = process.env.PAYPAL_POS_API_KEY;
    if ( ! apiKey ) {
        // No API key — reset to disconnected state so tests skip cleanly
        await requestUtils.activatePlugin( PLUGIN_SLUG );
        await resetOnboardingState();
        return;
    }

    await requestUtils.activatePlugin( PLUGIN_SLUG );
    await resetOnboardingState();
    await connectWithApiKey( posSettings, apiKey );
} );
