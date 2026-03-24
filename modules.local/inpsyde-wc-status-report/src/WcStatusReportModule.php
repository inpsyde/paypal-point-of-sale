<?php

declare(strict_types=1);

namespace Inpsyde\WcStatusReport;

use Inpsyde\Modularity\Module\ExecutableModule;
use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Inpsyde\Modularity\Module\ServiceModule;
use Psr\Container\ContainerInterface;

class WcStatusReportModule implements ServiceModule, ExecutableModule
{
    use ModuleClassNameIdTrait;

    /**
     * @inheritDoc
     */
    public function services(): array
    {
        return require __DIR__ . '/../services.php';
    }

    /**
     * @inheritDoc
     */
    public function run(ContainerInterface $container): bool
    {
        $renderer = $container->get('inpsyde.wc-status-report.renderer');
        assert($renderer instanceof StatusReportRendererInterface);

        add_action('woocommerce_system_status_report', static function () use ($renderer, $container) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $renderer->render($container->get('inpsyde.wc-status-report.report'));
        }, 20);

        return true;
    }
}
