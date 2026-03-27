<?php # -*- coding: utf-8 -*-
use Syde\PayPal\PointOfSale\Test\StubLoader;

$root = dirname(dirname(__DIR__));
$vendor = $root.'/vendor/';
if (!file_exists($vendor.'autoload.php')) {
    die("Please install via Composer before running tests.");
}
//$dotenv = Dotenv\Dotenv::createImmutable($root, '.env.phpunit');
//$dotenv->load();
require_once $vendor.'brain/monkey/inc/patchwork-loader.php';
require_once $vendor.'autoload.php';

require_once $root . '/../../packages.local/wc-product-contracts/src/ProductType.php';
require_once $root . '/../../packages.local/wc-product-contracts/src/ProductState.php';

require_once $root . '/../../tests/PHPUnit/Helper/StubLoader.php';

StubLoader::load();

unset($root);
unset($vendor);

putenv('TESTS_PATH='.__DIR__);
