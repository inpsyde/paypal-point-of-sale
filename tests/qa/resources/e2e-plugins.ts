import * as path from 'path';
import type { PluginZipEntry } from './types';

const FILES_DIR = path.join( __dirname, 'files' );

/**
 * Additional plugins that tests may need to install.
 * Add an entry here for each plugin zip kept in resources/files/.
 */
export const e2ePlugins: Record< string, PluginZipEntry > = {
    // example:
    // 'my-plugin': {
    //     path: path.join( FILES_DIR, 'my-plugin.zip' ),
    //     slug: 'my-plugin',
    // },
};

export { FILES_DIR };
