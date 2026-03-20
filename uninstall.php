<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale;

use Inpsyde\Queue\Queue\Job\Context;
use Inpsyde\Queue\Queue\Job\EphemeralJobRepository;
use Inpsyde\Queue\Queue\Job\Job;
use Syde\PayPal\PointOfSale\Onboarding\Job\ResetOnboardingJob;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface;

if (!defined('WP_UNINSTALL_PLUGIN')) {
    die('Direct access not allowed.');
}

(static function () {
    if (
        !class_exists(PluginModule::class)
        && file_exists(__DIR__ . '/vendor/autoload.php')
    ) {
        include_once __DIR__ . '/vendor/autoload.php';
    }

    $container = (require __DIR__ . '/bootstrap.php')(__DIR__);
    assert($container instanceof ContainerInterface);

    $resetJob = $container->get('paypal-pos.job.' . ResetOnboardingJob::TYPE);
    assert($resetJob instanceof Job);

    $resetJob->execute(
        Context::fromArray([]),
        new EphemeralJobRepository(),
        $container->get('inpsyde.queue.logger')
    );
})();
