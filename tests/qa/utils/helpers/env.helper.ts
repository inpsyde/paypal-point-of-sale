import { execFileSync } from 'node:child_process';
import { updateDotenv } from '@inpsyde/playwright-utils/build';
import type { AnyCli } from './pos-cli.helper';
import { runWpCli } from './pos-cli.helper';

const checkEnvVars = ( names: string[] ): void => {
    const missing = names.filter( ( name ) => ! process.env[ name ] );
    if ( missing.length ) {
        throw new Error( `Missing required environment variable(s): ${ missing.join( ', ' ) }` );
    }
};

// A reset wipes the DB row backing whatever ck_/cs_ pair is in .env, but leaves the now-dead
// strings in place. ensureWooCommerceApiKeys() only checks that they're present (deliberately,
// see its own comment), so without this every WC REST call after a reset 401s until someone
// notices and clears them by hand.
async function invalidateApiKeys(): Promise< void > {
    delete process.env.WC_API_KEY;
    delete process.env.WC_API_SECRET;
    await updateDotenv( '.env', { WC_API_KEY: '', WC_API_SECRET: '' } );
}

// Wipes the database and reinstalls WordPress + WooCommerce from scratch. Destructive and
// irreversible — use with care on shared environments.
//
// - wpenv (local): runs `wp db reset` + `wp core install` through the WP-CLI fixture.
// - ssh (Kinsta): DevOps deploys a dedicated per-environment `reset-wp.sh` script for this
//   purpose (see "How can QA reset a test environment?" —
//   https://inpsyde.atlassian.net/wiki/spaces/ENG/pages/6240338010). It also restores the
//   Kinsta MU plugin and clears Kinsta/WP caches, which a raw `wp core install` does not.
//   It must run as its own SSH command — the WP-CLI fixture always prefixes commands with
//   `wp `, so it can't invoke this script.
export async function resetEnvironment( cli: AnyCli ): Promise< void > {
    if ( process.env.WPCLI_ENV_TYPE === 'ssh' ) {
        await resetRemoteEnvironment( cli );
        return;
    }

    checkEnvVars( [ 'WP_BASE_URL', 'WP_USERNAME', 'WP_PASSWORD' ] );

    await runWpCli( cli, 'db reset --yes' );
    await invalidateApiKeys();
    await runWpCli(
        cli,
        // Single-quoted: WpEnvCli wraps the whole command in `bash -c "..."` (double
        // quotes), so double-quoting these values here would close that outer quote early
        // and split "PayPal POS E2E" into separate bash -c arguments, silently dropping
        // --admin_user/--admin_email. Single quotes are inert inside the outer double quotes.
        `core install --url='${ process.env.WP_BASE_URL }' --title='PayPal POS E2E' ` +
            `--admin_user='${ process.env.WP_USERNAME }' --admin_password='${ process.env.WP_PASSWORD }' ` +
            `--admin_email='test@test.com'`
    );
    await ensureWooCommercePlugin( cli );
    await ensureStorefrontTheme( cli );
}

// Runs DevOps' `reset-wp.sh`. Afterwards WordPress has a freshly generated random admin
// password — retrieve it via 1Password (communicated by DevOps) or `cat ~/.wp-cli/config.yml`
// on the environment, then update WP_USERNAME/WP_PASSWORD in .env before the next test run.
async function resetRemoteEnvironment( cli: AnyCli ): Promise< void > {
    checkEnvVars( [ 'SSH_LOGIN', 'SSH_HOST', 'SSH_PORT' ] );

    const wpVersion = process.env.WP_VERSION ?? '7.0';
    const wpType = process.env.WP_TYPE ?? 'single';
    const remoteCmd = `$HOME/bin/reset-wp.sh --wp-version=${ wpVersion } --wp-type=${ wpType }`;

    execFileSync(
        'ssh',
        [
            `${ process.env.SSH_LOGIN }@${ process.env.SSH_HOST }`,
            '-p',
            process.env.SSH_PORT as string,
            '-o',
            'StrictHostKeyChecking=no',
            remoteCmd,
        ],
        { stdio: 'inherit', timeout: 5 * 60_000 }
    );
    await invalidateApiKeys();

    // reset-wp.sh deletes all WordPress files, including plugins/themes — unlike a plain
    // `wp db reset` (wpenv path above), which only wipes the database and leaves them on
    // disk. WooCommerce is a hard prerequisite for the PayPal POS plugin, so it must be
    // reinstalled here before any further setup can run.
    await ensureWooCommercePlugin( cli );
    await ensureStorefrontTheme( cli );
}

// Idempotent: `wp plugin install` errors if the plugin already exists (expected on the
// wpenv path, where WooCommerce is never actually removed) — only that specific failure
// is swallowed here. Any other failure (e.g. WooCommerce requiring a newer WP core version
// than WP_VERSION installed) is a real problem and must not be hidden, or it only surfaces
// later as a confusing "plugin could not be found" error from the activate step.
async function ensureWooCommercePlugin( cli: AnyCli ): Promise< void > {
    await runWpCli( cli, 'plugin install woocommerce' ).catch( ( error: Error ) => {
        if ( ! /already installed/i.test( error.message ) ) {
            throw error;
        }
    } );
    await runWpCli( cli, 'plugin activate woocommerce' );

    // WooCommerce sets this transient on activation to redirect the next wp-admin load to
    // its setup wizard. Deleting it keeps automated runs on the page they actually navigate
    // to, instead of getting hijacked by the wizard.
    await runWpCli( cli, 'transient delete _wc_activation_redirect' ).catch( () => {} );
}

// Storefront is WooCommerce's reference theme — a freshly reset site keeps whatever
// WordPress' own default theme is, not Storefront, so it must be installed explicitly.
async function ensureStorefrontTheme( cli: AnyCli ): Promise< void > {
    await runWpCli( cli, 'theme install storefront' ).catch( ( error: Error ) => {
        if ( ! /already installed/i.test( error.message ) ) {
            throw error;
        }
    } );
    await runWpCli( cli, 'theme activate storefront' );
}
