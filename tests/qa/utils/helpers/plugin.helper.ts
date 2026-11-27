import type { RequestUtils, Plugins } from '@inpsyde/playwright-utils/build';
import type { PluginZipEntry } from '../../resources/types';

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
