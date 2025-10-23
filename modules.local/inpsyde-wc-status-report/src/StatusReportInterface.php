<?php

declare(strict_types=1);

namespace Inpsyde\WcStatusReport;

/**
 * The interface representing WC status report: the title (usually a plugin name) and the table items.
 */
interface StatusReportInterface
{
    public function getTitle(): string;

    /**
     * @return iterable<ReportItemInterface>
     */
    public function getItems(): iterable;
}
