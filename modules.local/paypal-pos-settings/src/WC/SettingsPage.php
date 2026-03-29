<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Settings\WC;

use Psr\Log\LoggerInterface;
use Throwable;
use WC_Settings_API;
use WC_Settings_Page;

/**
 * Page displayed in a tab in WooCommerce --> Settings.
 *
 * Delegates rendering and saving to WC_Settings_API (ZettleIntegration).
 */
class SettingsPage extends WC_Settings_Page
{
    private WC_Settings_API $settingsApi;

    private LoggerInterface $logger;

    private bool $isDebugMode;

    /**
     * @param WC_Settings_API $settingsApi
     * @param string $id
     * @param string $label
     * @param LoggerInterface $logger
     * @param bool $isDebugMode
     */
    public function __construct(
        WC_Settings_API $settingsApi,
        string $id,
        string $label,
        LoggerInterface $logger,
        bool $isDebugMode
    ) {

        $this->settingsApi = $settingsApi;

        $this->id = $id;
        $this->label = $label;

        $this->logger = $logger;

        $this->isDebugMode = $isDebugMode;

        parent::__construct();
    }

    /**
     * {@inheritDoc}
     *
     * @return array
     *
     * phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
     * phpcs:disable Inpsyde.CodeQuality.NoAccessors.NoGetter
     */
    public function get_settings()
    {
        // phpcs:enable

        return $this->settingsApi->get_form_fields();
    }

    /**
     * {@inheritDoc}
     *
     * @return void
     *
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
     * phpcs:disable Generic.Metrics.NestingLevel.TooHigh
     */
    public function output()
    {
        // phpcs:enable

        try {
            $this->settingsApi->admin_options();
        } catch (Throwable $exception) {
            $this->logger->critical('Settings output failed: ' . (string) $exception);

            try {
                do_action('inpsyde.zettle.settings.output-failed', $exception);
            } catch (Throwable $exc) {
                if ($this->isDebugMode) {
                    throw $exc;
                }
            }

            // if we didn't handle it in the event handler
            // (which currently should result in redirect, see IZET-174),
            // just show some basic error message
            ob_start(); ?>
            <h3 style="color: red;">
                <?php esc_html_e(
                    'A critical error occurred. Please check the WooCommerce logs for more details.',
                    'paypal-point-of-sale'
                ) ?>
            </h3>
            <?php echo ob_get_clean(); // WPCS: XSS ok.
        }
    }

    /**
     * {@inheritDoc}
     *
     * @return void
     *
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
     */
    public function save()
    {
        // phpcs:enable

        $this->settingsApi->process_admin_options();
    }
}
