import { defineConfig, devices } from '@playwright/test';
import type { BaseExtend } from '@inpsyde/playwright-utils/build';
import { WpCliEnvType } from '@inpsyde/playwright-utils/build/@types/wp-cli';
require( 'dotenv' ).config();

export default defineConfig< BaseExtend >( {
    testDir: 'tests/qa/tests',
    globalSetup: require.resolve( './global-setup' ),
    timeout: 2 * 60_000,
    expect: { timeout: 20 * 1000 },
    fullyParallel: false,
    forbidOnly: !! process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers: 1,
    snapshotDir: './snapshots',

    reporter: [
        [ 'list' ],
        [ 'html', { outputFolder: 'playwright-report', open: 'never' } ],
    ],

    use: {
        baseURL: process.env.WP_BASE_URL,
        storageState: process.env.STORAGE_STATE_PATH_ADMIN,
        ...devices[ 'Desktop Chrome' ],
        viewport: { width: 1280, height: 850 },
        ignoreHTTPSErrors: process.env.IGNORE_HTTPS_ERRORS === 'true',
        ...( process.env.NGROK_ENABLED === 'true' && {
            extraHTTPHeaders: { 'ngrok-skip-browser-warning': 'true' },
        } ),
        navigationTimeout: 90_000,
        actionTimeout: 30_000,

        ...( process.env.WP_BASIC_AUTH_USER && process.env.WP_BASIC_AUTH_PASS && {
            httpCredentials: {
                username: process.env.WP_BASIC_AUTH_USER,
                password: process.env.WP_BASIC_AUTH_PASS,
            },
        } ),

        trace: process.env.CI ? 'off' : 'retain-on-failure',
        screenshot: { mode: 'only-on-failure', fullPage: true },
        video: process.env.CI ? 'off' : { mode: 'retain-on-failure', size: { width: 1280, height: 850 } },

        cliConfig: {
            envType: ( process.env.WPCLI_ENV_TYPE ?? 'wpenv' ) as WpCliEnvType,
            path: process.env.WPCLI_PATH,
            ssh: {
                login: process.env.SSH_LOGIN,
                host: process.env.SSH_HOST,
                port: process.env.SSH_PORT,
                path: process.env.SSH_PATH,
            },
            vip: {
                appName: process.env.VIP_APP_NAME,
                env: process.env.VIP_ENV,
            },
        },
    },

    projects: [
        // ── Setup / teardown — runs AFTER shards that reset onboarding state ──
        {
            name: 'setup:paypal-pos',
            testMatch: /_setup\/paypal-pos\.setup\.ts/,
            teardown: 'teardown:paypal-pos',
            dependencies: [ 'shard:plugin-lifecycle', 'shard:onboarding' ],
        },
        {
            name: 'teardown:paypal-pos',
            testMatch: /_setup\/paypal-pos\.teardown\.ts/,
        },

        // ── Shards ────────────────────────────────────────────────────────────
        {
            name: 'shard:plugin-lifecycle',
            testMatch: /01-plugin-lifecycle\/.*\.spec\.ts/,
        },
        {
            name: 'shard:onboarding',
            testMatch: /02-onboarding\/.*\.spec\.ts/,
        },
        {
            name: 'shard:product-sync',
            testMatch: /03-product-sync\/.*\.spec\.ts/,
            dependencies: [ 'setup:paypal-pos' ],
        },
        {
            name: 'shard:stock-sync',
            testMatch: /04-stock-sync\/.*\.spec\.ts/,
            dependencies: [ 'setup:paypal-pos' ],
        },
    ],
} );
