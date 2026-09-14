<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale;

use Syde\Vendor\Zettle\Inpsyde\Modularity\Package;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\Context;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\EphemeralJobRepository;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\Job;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\Job\ResetOnboardingJob;
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die('Direct access not allowed.');
}
(static function () {
    if (!class_exists(PluginModule::class) && file_exists(__DIR__ . '/vendor/autoload.php')) {
        include_once __DIR__ . '/vendor/autoload.php';
    }
    $package = (require __DIR__ . '/bootstrap.php')(__DIR__ . '/zettle-pos-integration.php');
    assert($package instanceof Package);
    $container = $package->container();
    $resetJob = $container->get('paypal-pos.job.' . ResetOnboardingJob::TYPE);
    assert($resetJob instanceof Job);
    $resetJob->execute(Context::fromArray([]), new EphemeralJobRepository(), $container->get('inpsyde.queue.logger'));
})();
