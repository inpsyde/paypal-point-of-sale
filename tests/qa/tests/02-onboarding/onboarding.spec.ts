import { test, resetOnboarding, ensurePluginState } from '../../utils';
import { e2ePlugins } from '../../resources';

const INVALID_API_KEY =
	'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJ0ZXN0LWludmFsaWQiLCJpc3MiOiJwYXlwYWwiLCJpYXQiOjE3MDAwMDAwMDB9.SIG_INVALID_FAKE_DO_NOT_USE';

test.describe( 'Onboarding', () => {
	test.beforeEach( async ( { requestUtils, plugins, cli } ) => {
		await ensurePluginState( requestUtils, plugins, e2ePlugins.paypalPos );
		await resetOnboarding( cli );
	} );

	test( 'POS-570 | Onboarding — full connect with valid API key; smoke; critical;', async ( {
		posSettings,
		cli,
	} ) => {
		test.setTimeout( 5 * 60_000 );

		const apiKey = process.env.PAYPAL_POS_API_KEY;
		if ( ! apiKey ) {
			test.skip(
				true,
				'PAYPAL_POS_API_KEY not set — skipping live onboarding test'
			);
			return;
		}

		await posSettings.connect( apiKey, cli );
	} );

	test( 'POS-571 | Invalid API key shows Authentication failed screen; critical;', async ( {
		posSettings,
	} ) => {
		await posSettings.visit();
		await posSettings.assertWelcomeState();

		await posSettings.connectButton().click();
		await posSettings.page.waitForLoadState( 'load' );
		await posSettings.assertApiCredentialsState();

		await posSettings.apiKeyInput().fill( INVALID_API_KEY );
		await posSettings.authenticateButton().click();
		await posSettings.page.waitForLoadState( 'load' );

		await posSettings.assertInvalidCredentialsState();
	} );

	test( 'POS-646 | Start over after invalid key returns to API credentials screen; regression;', async ( {
		posSettings,
	} ) => {
		await posSettings.visit();

		await posSettings.connectButton().click();
		await posSettings.page.waitForLoadState( 'load' );
		await posSettings.apiKeyInput().fill( INVALID_API_KEY );
		await posSettings.authenticateButton().click();
		await posSettings.page.waitForLoadState( 'load' );
		await posSettings.assertInvalidCredentialsState();

		await posSettings.startOverButton().click();
		await posSettings.page.waitForLoadState( 'load' );
		await posSettings.assertApiCredentialsState();
	} );

	test( 'POS-572 | Reconnect after disconnect restores connected state; regression;', async ( {
		posSettings,
		cli,
	} ) => {
		test.setTimeout( 10 * 60_000 );

		const apiKey = process.env.PAYPAL_POS_API_KEY;
		if ( ! apiKey ) {
			test.skip(
				true,
				'PAYPAL_POS_API_KEY not set — skipping reconnect test'
			);
			return;
		}

		await posSettings.connect( apiKey, cli );
		await posSettings.disconnect();
		await posSettings.assertWelcomeState();

		await posSettings.connect( apiKey, cli );
	} );

	test( 'POS-577 | Disconnect removes connected state and returns to welcome screen; critical;', async ( {
		posSettings,
		cli,
	} ) => {
		test.setTimeout( 5 * 60_000 );

		const apiKey = process.env.PAYPAL_POS_API_KEY;
		if ( ! apiKey ) {
			test.skip(
				true,
				'PAYPAL_POS_API_KEY not set — skipping disconnect test'
			);
			return;
		}

		await posSettings.connect( apiKey, cli );
		await posSettings.disconnect();
		await posSettings.assertWelcomeState();
	} );
} );
