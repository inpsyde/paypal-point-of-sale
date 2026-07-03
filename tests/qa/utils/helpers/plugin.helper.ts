import type { RequestUtils, Plugins } from '@inpsyde/playwright-utils/build';
import type { PluginZipEntry } from '../../resources/types';

// Establishes "plugin installed and in the desired active state" from whatever the
// environment currently looks like — never assumes a previously-run suite left the plugin
// present. Folder numbering (01-, 02-, ...) only controls execution order, it is not a
// guarantee that suite N leaves the plugin in a state suite N+1 can rely on — e.g.
// 01-plugin-lifecycle's last test deletes the plugin entirely. Every suite that needs the
// plugin must call this itself rather than assuming it, including suites added later.
export async function ensurePluginState(
    requestUtils: RequestUtils,
    plugins: Plugins,
    plugin: PluginZipEntry,
    isActive = true
): Promise< void > {
    if ( ! ( await requestUtils.isPluginInstalled( plugin.slug ) ) ) {
        await plugins.installPluginFromFile( plugin.zipFilePath );
    }

    if ( isActive ) {
        await requestUtils.activatePlugin( plugin.slug );
    } else {
        await requestUtils.deactivatePlugin( plugin.slug );
    }
}
