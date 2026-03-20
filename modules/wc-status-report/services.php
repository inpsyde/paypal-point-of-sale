<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\WcStatusReportDebug;

use Syde\Vendor\Zettle\Inpsyde\WcStatusReport\ReportItemFactory;
use Syde\Vendor\Zettle\Inpsyde\WcStatusReport\ReportItemFactoryInterface;
use Syde\Vendor\Zettle\Inpsyde\WcStatusReport\StatusReport;
use Syde\Vendor\Zettle\Inpsyde\WcStatusReport\StatusReportInterface;
use Syde\Vendor\Zettle\Inpsyde\WcStatusReport\StatusReportRenderer;
use Syde\Vendor\Zettle\Inpsyde\WcStatusReport\StatusReportRendererInterface;
use Syde\Vendor\Zettle\Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;
return ['inpsyde.wc-status-report.item-factory' => static function (C $container): ReportItemFactoryInterface {
    return new ReportItemFactory();
}, 'inpsyde.wc-status-report.items' => static function (C $container): array {
    return [];
}, 'inpsyde.wc-status-report.report' => static function (C $container): StatusReportInterface {
    return new StatusReport($container->get('inpsyde.wc-status-report.plugin.name'), $container->get('inpsyde.wc-status-report.items'));
}, 'inpsyde.wc-status-report.renderer' => static function (C $container): StatusReportRendererInterface {
    return new StatusReportRenderer();
}];
