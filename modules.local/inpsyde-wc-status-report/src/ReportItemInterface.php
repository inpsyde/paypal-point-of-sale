<?php

declare(strict_types=1);

namespace Inpsyde\WcStatusReport;

/**
 * The interface representing report item data (a row in the WC status table)
 */
interface ReportItemInterface
{
    /**
     * The label displayed in the table.
     * @return string
     */
    public function getLabel(): string;

    /**
     * The label in the generated report.
     * @return string
     */
    public function getExportedLabel(): string;

    /**
     * @return mixed
     */
    public function getValue();
}
