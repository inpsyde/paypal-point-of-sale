import {
    testPluginInstallationFromFile,
    testPluginReinstallationFromFile,
    testPluginActivation,
    testPluginDeactivation,
    testPluginRemoval,
} from '@inpsyde/playwright-utils/build';
import { e2ePlugins } from '../../resources';

// POS-565 fails until the uninstall.php PHP bug is fixed: deletePlugin triggers uninstall.php
// → bootstrap.php → addModule( new InpsydeDebugModule() ) → TypeError/class-not-found on PHP 8.x.
testPluginInstallationFromFile( 'POS-565', e2ePlugins.paypalPos, '; smoke; critical;' );
testPluginReinstallationFromFile( 'POS-900', e2ePlugins.paypalPos, '; critical;' );
testPluginActivation( 'POS-XXX', e2ePlugins.paypalPos, '; critical;' );
testPluginDeactivation( 'POS-XXX', e2ePlugins.paypalPos, '; critical;' );
// POS-003 fails for the same reason as POS-565: REST DELETE runs uninstall.php which crashes.
testPluginRemoval( 'POS-XXX', e2ePlugins.paypalPos, '; critical;' );
