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
