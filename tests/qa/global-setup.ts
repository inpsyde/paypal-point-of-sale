import { FullConfig, type HTTPCredentials } from '@playwright/test';
import { restLogin, guestStorageState } from '@inpsyde/playwright-utils/build';

async function globalSetup( config: FullConfig ) {
	const projectUse = config.projects[ 0 ].use;
	// playwright.config.ts sets one set of HTTP credentials, never a list. pw-utils
	// accepts only one, while Playwright 1.64 also allows a list in project options.

	await restLogin( {
		baseURL: projectUse.baseURL as string,
		storageStatePath: String( projectUse.storageState ),
		httpCredentials: projectUse.httpCredentials as
			| HTTPCredentials
			| undefined,
		user: {
			username: process.env.WP_USERNAME as string,
			password: process.env.WP_PASSWORD as string,
		},
	} );

	await guestStorageState( {
		baseURL: projectUse.baseURL as string,
		httpCredentials: projectUse.httpCredentials as
			| HTTPCredentials
			| undefined,
		storageStatePath: `${ process.env.STORAGE_STATE_PATH }/guest.json`,
	} );
}

export default globalSetup;
