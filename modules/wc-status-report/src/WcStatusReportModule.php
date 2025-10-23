<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\WcStatusReport;

use Syde\Vendor\Zettle\Dhii\Container\ServiceProvider;
use Syde\Vendor\Zettle\Dhii\Modular\Module\ModuleInterface;
use Syde\Vendor\Zettle\Interop\Container\ServiceProviderInterface;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface;
class WcStatusReportModule implements ModuleInterface
{
    /**
     * @inheritDoc
     */
    public function setup(): ServiceProviderInterface
    {
        return new ServiceProvider(require __DIR__ . '/../services.php', require __DIR__ . '/../extensions.php');
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
