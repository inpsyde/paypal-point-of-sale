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

// The WC <-> POS sync relies on the WooCommerce REST API. Creates key/secret once and
// persists them to .env (and the current process) so later steps can use them immediately.
export async function ensureWooCommerceApiKeys( wooCommerceUtils: WooCommerceUtils ): Promise< void > {
    if ( await wooCommerceUtils.apiKeysExist() ) {
        return;
    }

    const apiKeys = await wooCommerceUtils.createApiKeys();
    if ( ! process.env.CI ) {
        await updateDotenv( '.env', apiKeys );
    }
    for ( const [ key, value ] of Object.entries( apiKeys ) ) {
        process.env[ key ] = String( value );
    }
}

// Silences transactional e-mails so test runs don't send real e-mails to test customers.
export async function disableWooCommerceEmails( wooCommerceApi: WooCommerceApi ): Promise< void > {
    const emailIds = [
        'email_new_order',
        'email_cancelled_order',
        'email_failed_order',
        'email_customer_failed_order',
        'email_customer_on_hold_order',
        'email_customer_processing_order',
        'email_customer_completed_order',
        'email_customer_refunded_order',
        'email_customer_note',
        'email_customer_reset_password',
        'email_customer_new_account',
    ];

    for ( const id of emailIds ) {
        await wooCommerceApi.updateEmailSubSettings( id as never, { enabled: 'no' } );
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
