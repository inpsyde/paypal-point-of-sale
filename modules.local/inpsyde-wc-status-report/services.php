<?php

declare(strict_types=1);

namespace Inpsyde\WcStatusReportDebug;

use Inpsyde\WcStatusReport\ReportItemFactory;
use Inpsyde\WcStatusReport\ReportItemFactoryInterface;
use Inpsyde\WcStatusReport\StatusReport;
use Inpsyde\WcStatusReport\StatusReportInterface;
use Inpsyde\WcStatusReport\StatusReportRenderer;
use Inpsyde\WcStatusReport\StatusReportRendererInterface;
use Psr\Container\ContainerInterface as C;

return [
    'inpsyde.wc-status-report.item-factory' => static function (C $container): ReportItemFactoryInterface {
        return new ReportItemFactory();
    },
    'inpsyde.wc-status-report.items' => static function (C $container): array {
        return [];
    },
    'inpsyde.wc-status-report.report' => static function (C $container): StatusReportInterface {
        return new StatusReport(
            $container->get('inpsyde.wc-status-report.plugin.name'),
            $container->get('inpsyde.wc-status-report.items')
        );
    },
    'inpsyde.wc-status-report.renderer' => static function (C $container): StatusReportRendererInterface {
        return new StatusReportRenderer();
    },
];
