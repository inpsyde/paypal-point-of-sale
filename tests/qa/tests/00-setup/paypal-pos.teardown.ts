import { exec } from 'child_process';
import { promisify } from 'util';
import * as path from 'path';
import { test as teardown } from '../../utils';

const PLUGIN_ROOT = path.resolve( __dirname, '..', '..', '..', '..' );
const execAsync = promisify( exec );

teardown( 'Reset PayPal POS state', async () => {
    await execAsync( 'npx @wordpress/env run cli wp zettle reset onboarding complete', {
        cwd: PLUGIN_ROOT,
        timeout: 60_000,
    } );
} );
