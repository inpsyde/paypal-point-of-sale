import { defineConfig, devices } from '@playwright/test';
import type { BaseExtend } from '@inpsyde/playwright-utils/build';
import { WpCliEnvType } from '@inpsyde/playwright-utils/build/@types/wp-cli';
import dotenv from 'dotenv';
import path from 'path';

const dotenvPath = process.env.CI
    ? path.resolve( __dirname, '.env.ci' )
    : undefined;
dotenv.config( { path: dotenvPath } );

export default defineConfig< BaseExtend >( {
    testDir: 'tests/qa/tests',
    globalSetup: require.resolve( './tests/qa/global-setup' ),
    timeout: 2 * 60_000,
    expect: { timeout: 20 * 1000 },
    fullyParallel: false,
    forbidOnly: !! process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers: 1,
    snapshotDir: './snapshots',

    reporter: process.env.XRAY_TEST_EXEC_KEY
        ? [
            [ 'list' ],
            [
                '@inpsyde/playwright-utils/build/integration/jira/xray-reporter.js',
                {
                    apiClient: {
                        client_id: process.env.XRAY_CLIENT_ID ?? '',
                        client_secret: process.env.XRAY_CLIENT_SECRET ?? '',
                    },
                    testExecutionKey: process.env.XRAY_TEST_EXEC_KEY,
                },
            ],
        ]
        : [
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
            ...( process.env.SSH_LOGIN && process.env.SSH_HOST && {
                ssh: {
                    login: process.env.SSH_LOGIN,
                    host: process.env.SSH_HOST,
                    port: process.env.SSH_PORT,
                    path: process.env.SSH_PATH,
                },
            } ),
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
