<?php
$root = dirname(dirname(__DIR__));
$vendor = $root.'/vendor/';
if (!file_exists($vendor.'autoload.php')) {
    die("Please install via Composer before running tests.");
}

putenv('TESTS_PATH='.__DIR__);
