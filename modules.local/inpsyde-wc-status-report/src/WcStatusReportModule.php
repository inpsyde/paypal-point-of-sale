<?php

declare(strict_types=1);

namespace Inpsyde\WcStatusReport;

use Dhii\Container\ServiceProvider;
use Dhii\Modular\Module\ModuleInterface;
use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerInterface;

class WcStatusReportModule implements ModuleInterface
{

    /**
     * @inheritDoc
     */
    public function setup(): ServiceProviderInterface
    {
        return new ServiceProvider(
            require __DIR__ . '/../services.php',
            require __DIR__ . '/../extensions.php'
        );
    }

    /**
     * @inheritDoc
     */
    public function run(ContainerInterface $container): void
    {
        $renderer = $container->get('inpsyde.wc-status-report.renderer');
        assert($renderer instanceof StatusReportRendererInterface);

        add_action('woocommerce_system_status_report', static function () use ($renderer, $container) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $renderer->render($container->get('inpsyde.wc-status-report.report'));
        }, 20);
    }
}
