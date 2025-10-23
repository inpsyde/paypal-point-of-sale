<?php

declare(strict_types=1);

namespace Inpsyde\WcStatusReport;

/**
 * The interface for creating report items.
 * @see ReportItemInterface
 */
interface ReportItemFactoryInterface
{
    public function createReportItem(string $label, string $exportedLabel, $value): ReportItemInterface;
}
