<?php # -*- coding: utf-8 -*-
use Syde\PayPal\PointOfSale\Test\StubLoader;

$root = dirname(dirname(__DIR__));
$vendor = $root.'/vendor/';
if (!file_exists($vendor.'autoload.php')) {
    die("Please install via Composer before running tests.");
}

$envPhpUnitExists = file_exists($root.'/.env.phpunit');

/**
 * If we need more to detect the current CI, use this package:
 * https://github.com/OndraM/ci-detector
 */
if (!$envPhpUnitExists && getenv('GITHUB_ACTIONS') === false) {
    exit('No ".env.phpunit" found. Please copy the ".env.phpunit.dist" to ".env.phpunit"' . PHP_EOL);
}

if ($envPhpUnitExists) {
    $dotenv = Dotenv\Dotenv::createImmutable($root, '.env.phpunit');
    $dotenv->load();
}

// load stubs
StubLoader::load();

require_once $vendor.'brain/monkey/inc/patchwork-loader.php';
require_once $vendor.'autoload.php';
require_once $vendor.'inpsyde/wc-product-contracts/src/ProductState.php';
require_once $vendor.'inpsyde/wc-product-contracts/src/ProductType.php';
unset($root);
unset($vendor);

putenv('TESTS_PATH='.__DIR__);
ini_set('error_reporting', E_ALL);
