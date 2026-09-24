import type {
	VipCli,
	SshCli,
	WpEnvCli,
	LocalhostCli,
	DdevCli,
	RequestUtils,
	Plugins,
	WooCommerceUtils,
	WooCommerceApi,
} from '@inpsyde/playwright-utils/build';
import type { PosSettingsPage } from '../admin';
import { ensurePluginState } from './plugin.helper';
import { ensureStoreConfigured } from './woocommerce.helper';
import { e2ePlugins } from '../../resources';

export type AnyCli = VipCli | SshCli | WpEnvCli | LocalhostCli | DdevCli;

export async function runWpCli(
	cli: AnyCli,
	command: string
): Promise< string > {
	return ( cli as any ).execute( command );
}

export async function resetOnboarding( cli: AnyCli ): Promise< void > {
	await runWpCli( cli, 'zettle reset onboarding complete' );
}

export async function processQueue( cli: AnyCli ): Promise< void > {
	await runWpCli( cli, 'zettle queue process' ).catch( () => {} );
}

export async function syncProduct(
	cli: AnyCli,
	productId: number
): Promise< void > {
	await runWpCli( cli, `zettle sync product ${ productId }` );
}

// Mirrors Syde\PayPal\PointOfSale\Sync\PriceSyncMode.
export const PriceSyncMode = {
	ENABLED: 'gross',
	DISABLED: 'zero',
} as const;

/**
 * Read the `woocommerce_zettle_settings` option, or {} if it doesn't exist yet.
 * @param cli
 */
export async function getZettleSettings(
	cli: AnyCli
): Promise< Record< string, unknown > > {
	try {
		const raw = await runWpCli(
			cli,
			'option get woocommerce_zettle_settings --format=json'
		);
		return JSON.parse( raw );
	} catch {
		return {};
	}
}

/**
 * Read the persisted price-sync strategy — a `PriceSyncMode` value, or '' if unset.
 * SyncModule forces this to DISABLED on every wp-admin request while the store's
 * currency doesn't match the PayPal POS account, regardless of what's saved here.
 * @param cli
 */
export async function getPriceSyncStrategy( cli: AnyCli ): Promise< string > {
	const settings = await getZettleSettings( cli );
	return String( settings.sync_price_strategy ?? '' );
}

/**
 * Directly set the persisted price-sync strategy, simulating a merchant saving the
 * settings form — without needing to drive the WC_Integration form field in the UI.
 * @param cli
 * @param mode
 */
export async function setPriceSyncStrategy(
	cli: AnyCli,
	mode: string
): Promise< void > {
	const settings = await getZettleSettings( cli );
	settings.sync_price_strategy = mode;
	await runWpCli(
		cli,
		`option update woocommerce_zettle_settings '${ JSON.stringify(
			settings
		) }' --format=json`
	);
}

export async function ensurePosConnected(
	posSettings: PosSettingsPage,
	cli: AnyCli
): Promise< void > {
	const apiKey = process.env.PAYPAL_POS_API_KEY;
	if ( ! apiKey ) {
		return;
	}

	await posSettings.visit();
	if ( await posSettings.productsCountText().isVisible() ) {
		return;
	}

	await posSettings.connect( apiKey, cli );
}

export async function ensurePosTestReady( fixtures: {
	requestUtils: RequestUtils;
	plugins: Plugins;
	wooCommerceUtils: WooCommerceUtils;
	wooCommerceApi: WooCommerceApi;
	posSettings: PosSettingsPage;
	cli: AnyCli;
} ): Promise< void > {
	await ensurePluginState(
		fixtures.requestUtils,
		fixtures.plugins,
		e2ePlugins.paypalPos
	);
	await ensureStoreConfigured(
		fixtures.wooCommerceUtils,
		fixtures.wooCommerceApi
	);
	await ensurePosConnected( fixtures.posSettings, fixtures.cli );
}
