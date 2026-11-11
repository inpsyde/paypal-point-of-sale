<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\WcStatusReport;

class StatusReportRenderer implements StatusReportRendererInterface
{
    /**
     * @inheritDoc
     */
    public function render(StatusReportInterface $report): string
    {
        ob_start();
        ?>
        <table class="wc_status_table widefat" cellspacing="0">
            <thead>
            <tr>
                <th colspan="3" data-export-label="<?php 
        echo esc_attr($report->getTitle());
        ?>"><h2><?php 
        echo esc_html($report->getTitle());
        ?></h2></th>
            </tr>
            </thead>
            <tbody>
            <?php 
        foreach ($report->getItems() as $item) {
            // WC uses the 3rd column for export, so we need to add an empty 2nd column
            ?>
                <tr>
                    <td data-export-label="<?php 
            echo esc_attr($item->getExportedLabel());
            ?>">
                        <?php 
            echo esc_html($item->getLabel());
            ?>
                    </td>
                    <td style="display: none;"></td>
                    <td><?php 
            echo esc_html((string) $item->getValue());
            ?></td>
                </tr>
                <?php 
        }
        ?>
            </tbody>
        </table>
        <?php 
        return (string) ob_get_clean();
    }
}
