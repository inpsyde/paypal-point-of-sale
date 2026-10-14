<?php

declare(strict_types=1);

namespace Inpsyde\WcStatusReport;

class ReportItemFactory implements ReportItemFactoryInterface
{
    /**
     * @inheritDoc
     */
    public function createReportItem(string $label, string $exportedLabel, mixed $value): ReportItemInterface
    {
        return new ReportItem($label, $exportedLabel, $value);
    }
}
