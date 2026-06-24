import {
    testPluginInstallationFromFile,
    testPluginReinstallationFromFile,
    testPluginActivation,
    testPluginDeactivation,
    testPluginRemoval,
} from '@inpsyde/playwright-utils/build';
import { e2ePlugins } from '../../resources';

testPluginInstallationFromFile( 'POS-565', e2ePlugins.paypalPos, '; smoke; critical;' );
testPluginReinstallationFromFile( 'POS-900', e2ePlugins.paypalPos, '; critical;' );
testPluginActivation( 'POS-XXX', e2ePlugins.paypalPos, '; critical;' );
testPluginDeactivation( 'POS-XXX', e2ePlugins.paypalPos, '; critical;' );
testPluginRemoval( 'POS-XXX', e2ePlugins.paypalPos, '; critical;' );
