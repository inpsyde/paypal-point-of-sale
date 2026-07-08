import { updateDotenv } from '@inpsyde/playwright-utils/build';
import type { WooCommerceUtils, WooCommerceApi } from '@inpsyde/playwright-utils/build';
import { shopSettings, taxSettings } from '../../resources';

// GB, not US — PayPal POS does not sync US tax rates at all (a separate, untested merchant
// scenario), so USA would leave tax sync effectively unverified by default.
const country = process.env.WC_DEFAULT_COUNTRY ?? 'uk';

// A fresh WooCommerce install defaults to "Coming soon", which hides prices/checkout from
// anything that isn't an admin. POS sync tests read prices, so the store must be live.
export async function setupSiteVisibility( wooCommerceUtils: WooCommerceUtils ): Promise< void > {
    await wooCommerceUtils.setSiteVisibility( 'live' );
}

// Checks our own usable credentials, not just whether some key exists server-side — WC
// never exposes a secret again after creation, so an unrelated existing key is useless to us.
export async function ensureWooCommerceApiKeys( wooCommerceUtils: WooCommerceUtils ): Promise< void > {
    if ( process.env.WC_API_KEY && process.env.WC_API_SECRET ) {
        return;
    }

    const apiKeys = await wooCommerceUtils.createApiKeys();
    await updateDotenv( '.env', apiKeys );
    for ( const [ key, value ] of Object.entries( apiKeys ) ) {
        process.env[ key ] = String( value );
    }
}

// Country/currency must match whatever the PayPal POS sandbox account is configured for —
// mismatched settings on either side make product/price sync results meaningless. Set
// WC_DEFAULT_COUNTRY in .env if the sandbox isn't US-based (see shopSettings for the
// available keys, re-exported from @inpsyde/playwright-utils).
export async function setupGeneralSettings( wooCommerceApi: WooCommerceApi ): Promise< void > {
    await wooCommerceApi.updateGeneralSettings( shopSettings[ country ].general );
}

// PayPal POS enforces per-country allowed VAT rates (GB: 20/12.5/5/4/0) and rejects anything
// else with VAT_NOT_ALLOWED_IN_COUNTRY. The library's generic "worldwide 10%" fixture is not
// a valid UK rate, so it fails every product/stock sync once the store's country is GB (USA
// doesn't care, since PayPal POS doesn't tax-sync US stores at all). Use the UK standard rate.
const ukVatRate = {
    country: 'GB',
    state: '',
    cities: [],
    postcodes: [],
    rate: '20.0000',
    name: 'UK Standard Rate 20%',
    shipping: true,
};

export async function setupTaxes( wooCommerceUtils: WooCommerceUtils ): Promise< void > {
    await wooCommerceUtils.setTaxes( {
        options: taxSettings.including.options,
        rates: [ ukVatRate ],
    } );
}

// Lets a spec file run standalone without depending on setup:woocommerce having already run.
// Safe to call unconditionally on every test, unlike setup:env's destructive `wp db reset` —
// submitting the same site-visibility form, general settings, and tax rate the store already
// has is a harmless no-op (createTax itself looks up the rate by name before creating one),
// so there's nothing to "check first" here. The destructive reset stays a deliberate,
// manual-only step (see E2E-TESTS.md's Test Dependency Model).
export async function ensureStoreConfigured(
    wooCommerceUtils: WooCommerceUtils,
    wooCommerceApi: WooCommerceApi
): Promise< void > {
    await ensureWooCommerceApiKeys( wooCommerceUtils );
    await setupSiteVisibility( wooCommerceUtils );
    await setupGeneralSettings( wooCommerceApi );
    await setupTaxes( wooCommerceUtils );
}
