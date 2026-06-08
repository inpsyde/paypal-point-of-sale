import { FullConfig } from '@playwright/test';
import { restLogin, guestStorageState } from '@inpsyde/playwright-utils/build';

async function globalSetup( config: FullConfig ) {
    const projectUse = config.projects[ 0 ].use;

    await restLogin( {
        baseURL: projectUse.baseURL as string,
        storageStatePath: String( projectUse.storageState ),
        httpCredentials: projectUse.httpCredentials,
        user: {
            username: process.env.WP_USERNAME as string,
            password: process.env.WP_PASSWORD as string,
        },
    } );

    await guestStorageState( {
        baseURL: projectUse.baseURL as string,
        httpCredentials: projectUse.httpCredentials,
        storageStatePath: `${ process.env.STORAGE_STATE_PATH }/guest.json`,
    } );
}

export default globalSetup;
