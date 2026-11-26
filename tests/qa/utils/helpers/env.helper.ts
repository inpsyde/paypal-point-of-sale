import { execFileSync } from 'node:child_process';
import { test, updateDotenv } from '@inpsyde/playwright-utils/build';
import type { AnyCli } from './pos-cli.helper';
import { runWpCli } from './pos-cli.helper';

const checkEnvVars = ( names: string[] ): void => {
	const missing = names.filter( ( name ) => ! process.env[ name ] );
	if ( missing.length ) {
		throw new Error(
			`Missing required environment variable(s): ${ missing.join(
				', '
			) }`
		);
	}
};

/**
 * Read an env var a test cannot run without. When it is missing, skip the test locally,
 * but fail it in CI, so a missing secret can never make a CI run look green.
 * @param name
 */
export function requireEnv( name: string ): string {
	const value = process.env[ name ];
	if ( value ) {
		return value;
	}
	if ( process.env.CI ) {
		throw new Error(
			`${ name } is not set. It is required in CI; check the env file secret.`
		);
	}
	test.skip( true, `${ name } not set — skipping locally` );
	return '';
}

async function invalidateApiKeys(): Promise< void > {
	delete process.env.WC_API_KEY;
	delete process.env.WC_API_SECRET;
	await updateDotenv( '.env', { WC_API_KEY: '', WC_API_SECRET: '' } );
}

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
		`core install --url='${ process.env.WP_BASE_URL }' --title='PayPal POS E2E' ` +
			`--admin_user='${ process.env.WP_USERNAME }' --admin_password='${ process.env.WP_PASSWORD }' ` +
			`--admin_email='test@test.com'`
	);
	await ensureWooCommercePlugin( cli );
	await ensureStorefrontTheme( cli );
}

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
	await ensureWooCommercePlugin( cli );
	await ensureStorefrontTheme( cli );
}

async function ensureWooCommercePlugin( cli: AnyCli ): Promise< void > {
	await runWpCli( cli, 'plugin install woocommerce' ).catch(
		( error: Error ) => {
			if ( ! /already installed/i.test( error.message ) ) {
				throw error;
			}
		}
	);
	await runWpCli( cli, 'plugin activate woocommerce' );
	await runWpCli( cli, 'transient delete _wc_activation_redirect' ).catch(
		() => {}
	);
}

async function ensureStorefrontTheme( cli: AnyCli ): Promise< void > {
	await runWpCli( cli, 'theme install storefront' ).catch(
		( error: Error ) => {
			if ( ! /already installed/i.test( error.message ) ) {
				throw error;
			}
		}
	);
	await runWpCli( cli, 'theme activate storefront' );
}
