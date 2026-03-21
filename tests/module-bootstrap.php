<?php

/**
 * Bootstrap for running module PHPUnit tests from the project root.
 *
 * Loads the root autoloader first (for Mozart-scoped Syde\Vendor\Zettle\*
 * classes in lib/packages/), then the module's own autoloader (for module
 * classes and dev dependencies like Brain\Monkey).
 *
 * The module directory is resolved from PHPUnit's working directory.
 */

$rootDir = dirname(__DIR__);
$moduleDir = getcwd();

// Root autoloader — provides Syde\Vendor\Zettle\* from lib/packages/.
$rootAutoloader = $rootDir . '/vendor/autoload.php';
if (!file_exists($rootAutoloader)) {
    fwrite(STDERR, "Root vendor/autoload.php not found. Run 'composer install' in the project root.\n");
    exit(1);
}
require_once $rootAutoloader;

// Module autoloader — provides the module's own classes and dev dependencies.
$moduleAutoloader = $moduleDir . '/vendor/autoload.php';
if (file_exists($moduleAutoloader)) {
    require_once $moduleAutoloader;
}

// Module bootstrap — run the module's original bootstrap if it exists,
// now that both autoloaders are registered.
$moduleBootstrap = $moduleDir . '/tests/PHPUnit/bootstrap.php';
if (file_exists($moduleBootstrap)) {
    require_once $moduleBootstrap;
}

putenv('TESTS_PATH=' . $moduleDir . '/tests/PHPUnit');
