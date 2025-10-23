<?php

declare(strict_types=1);

namespace Inpsyde\WcStatusReport;

/**
 * The interface for rendering WC status table (for WC --> Status page).
 */
interface StatusReportRendererInterface
{
    /**
     * Renders the WC status table for the given data.
     */
    public function render(StatusReportInterface $report): string;
}
