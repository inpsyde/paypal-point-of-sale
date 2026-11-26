import {
	testPluginInstallationFromFile,
	testPluginReinstallationFromFile,
	testPluginActivation,
	testPluginDeactivation,
	testPluginRemoval,
} from '@inpsyde/playwright-utils/build';
import { e2ePlugins } from '../../resources';

testPluginInstallationFromFile(
	'POS-565',
	e2ePlugins.paypalPos,
	'; smoke; critical;'
);
testPluginReinstallationFromFile(
	'POS-633',
	e2ePlugins.paypalPos,
	'; critical;'
);
testPluginActivation( 'POS-637', e2ePlugins.paypalPos, '; critical;' );
testPluginDeactivation( 'POS-636', e2ePlugins.paypalPos, '; critical;' );
testPluginRemoval( 'POS-638', e2ePlugins.paypalPos, '; critical;' );
