import { updateDotenv } from '@inpsyde/playwright-utils/build';
import type {
	WooCommerceUtils,
	WooCommerceApi,
} from '@inpsyde/playwright-utils/build';
import { shopSettings, taxSettings } from '../../resources';

const country = process.env.WC_DEFAULT_COUNTRY ?? 'uk';

export async function setupSiteVisibility(
	wooCommerceUtils: WooCommerceUtils
): Promise< void > {
	await wooCommerceUtils.setSiteVisibility( 'live' );
}

let apiKeysValidated = false;

export async function ensureWooCommerceApiKeys(
	wooCommerceUtils: WooCommerceUtils,
	wooCommerceApi: WooCommerceApi
): Promise< void > {
	if ( apiKeysValidated ) {
		return;
	}

	if ( process.env.WC_API_KEY && process.env.WC_API_SECRET ) {
		try {
			await wooCommerceApi.wcRequest( 'get', 'settings/general' );
			apiKeysValidated = true;
			return;
		} catch {
			// Falls through to regenerate below.
		}
	}

	const apiKeys = await wooCommerceUtils.createApiKeys();
	await updateDotenv( '.env', apiKeys );
	for ( const [ key, value ] of Object.entries( apiKeys ) ) {
		process.env[ key ] = String( value );
	}
	apiKeysValidated = true;
}

export async function setupGeneralSettings(
	wooCommerceApi: WooCommerceApi
): Promise< void > {
	await wooCommerceApi.updateGeneralSettings(
		shopSettings[ country ].general
	);
}
/*
 PayPal POS enforces per-country allowed VAT rates (GB: 20/12.5/5/4/0) and rejects anything
 else with VAT_NOT_ALLOWED_IN_COUNTRY. The library's generic "worldwide 10%" fixture is not
 a valid UK rate, so it fails every product/stock sync once the store's country is GB (USA
 doesn't care, since PayPal POS doesn't tax-sync US stores at all). Use the UK standard rate.
*/
const ukVatRate = {
	country: 'GB',
	state: '',
	cities: [],
	postcodes: [],
	rate: '20.0000',
	name: 'UK Standard Rate 20%',
	shipping: true,
};

export async function setupTaxes(
	wooCommerceUtils: WooCommerceUtils
): Promise< void > {
	await wooCommerceUtils.setTaxes( {
		options: taxSettings.including.options,
		rates: [ ukVatRate ],
	} );
}

export async function ensureStoreConfigured(
	wooCommerceUtils: WooCommerceUtils,
	wooCommerceApi: WooCommerceApi
): Promise< void > {
	await ensureWooCommerceApiKeys( wooCommerceUtils, wooCommerceApi );
	await setupSiteVisibility( wooCommerceUtils );
	await setupGeneralSettings( wooCommerceApi );
	await setupTaxes( wooCommerceUtils );
}
