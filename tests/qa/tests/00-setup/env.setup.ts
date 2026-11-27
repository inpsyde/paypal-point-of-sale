import { restLogin } from '@inpsyde/playwright-utils/build';
import { test as setup, resetEnvironment } from '../../utils';

setup( 'Setup: Reset Environment', async ( { cli }, testInfo ) => {
	setup.skip(
		process.env.E2E_CONFIRM_RESET !== 'true',
		'Set E2E_CONFIRM_RESET=true to run this destructive reset.'
	);

	await resetEnvironment( cli );

	// The reset logs out the admin session that global-setup saved. Log in again,
	// so the next shard's first browser page doesn't open on wp-login.php.
	const { baseURL, storageState, httpCredentials } = testInfo.project.use;
	await restLogin( {
		baseURL: baseURL as string,
		storageStatePath: String( storageState ),
		httpCredentials,
		user: {
			username: process.env.WP_USERNAME as string,
			password: process.env.WP_PASSWORD as string,
		},
	} );
} );
