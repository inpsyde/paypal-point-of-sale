<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Onboarding\Settings\FieldRenderer;

use Syde\PayPal\PointOfSale\Onboarding\OnboardingState as S;
use Syde\PayPal\PointOfSale\Onboarding\Settings\OnboardingStepper;
use Syde\PayPal\PointOfSale\Onboarding\Settings\View\OnboardingView;
use Syde\PayPal\PointOfSale\Settings\FieldRenderer\FieldRendererInterface;
use WC_Settings_API;

/**
 * Class OnboardingFieldRenderer
 * phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 *
 * @package Syde\PayPal\PointOfSale\Onboarding\Settings
 */
class OnboardingFieldRenderer implements FieldRendererInterface
{
    private string $currentState;

    private OnboardingView $view;

    private OnboardingStepper $stepper;

    public function __construct(
        string $currentState,
        OnboardingView $view,
        OnboardingStepper $stepper
    ) {

        $this->view = $view;
        $this->currentState = $currentState;
        $this->stepper = $stepper;
    }

    /**
     * @param string $fieldId
     * @param array $fieldConfig
     * @param WC_Settings_API $settingsApi
     * @return bool
     */
    public function accepts(string $fieldId, array $fieldConfig, WC_Settings_API $settingsApi): bool
    {
        return array_key_exists('type', $fieldConfig) && $fieldConfig['type'] === 'zettle-onboarding';
    }

    /**
     * @param string $fieldId
     * @param array $fieldConfig
     * @param WC_Settings_API $settingsApi
     * phpcs:disable Syde.Functions.FunctionLength.TooLong
     * @return string
     */
    public function render(
        string $fieldId,
        array $fieldConfig,
        WC_Settings_API $settingsApi
    ): string {

        do_action('inpsyde.zettle.onboarding.rendering-started');

        $fieldKey = $settingsApi->get_field_key($fieldId);

        $fieldConfig = array_merge(
            [
                'title' => '',
                'disabled' => false,
                'class' => '',
                'css' => '',
                'placeholder' => '',
                'type' => 'text',
                'desc_tip' => false,
                'description' => '',
                'custom_attributes' => [],
            ],
            $fieldConfig
        );

        ob_start(); ?>

        <tr valign="top">
            <th scope="row" class="titledesc">
                <?php
                // phpcs:ignore WordPress.Security.EscapeOutput
                echo $this->renderTableHead($fieldKey, $fieldConfig, $settingsApi);
                ?>
            </th>

            <td class="forminp">
                <div class="zettle-settings-onboarding-container">
                    <?php
                    // phpcs:ignore WordPress.Security.EscapeOutput
                    echo $this->renderTableContent();
                    ?>
                </div>
            </td>
        </tr>

        <?php return (string) ob_get_clean();
    }

    /**
     * @param string $fieldKey
     * @param array $fieldConfig
     * @param WC_Settings_API $settingsApi
     *
     * @return string
     */
    protected function renderTableHead(
        string $fieldKey,
        array $fieldConfig,
        WC_Settings_API $settingsApi
    ): string {

        ob_start(); ?>

        <div class="zettle-settings-onboarding-caption">
            <div class="zettle-settings-onboarding-caption-title">
                <?php if ($this->currentState !== S::WELCOME) : ?>
                    <label for="<?php echo esc_attr($fieldKey); ?>">
                        <?php
                            echo wp_kses_post($fieldConfig['title']);
                            // phpcs:ignore WordPress.Security.EscapeOutput
                            echo $settingsApi->get_tooltip_html($fieldConfig);
                        ?>
                    </label>
                <?php endif; ?>
            </div>

            <?php if ($this->stepper->canRender()) : ?>
                <div class="zettle-settings-onboarding-caption-stepper">
                    <?php echo wp_kses_post($this->stepper->render()); ?>
                </div>
            <?php endif; ?>
        </div>

        <?php return (string) ob_get_clean();
    }

    /**
     * @return string
     */
    protected function renderTableContent(): string
    {
        ob_start(); ?>

        <div class="zettle-settings-onboarding-header">
            <?php
            // phpcs:ignore WordPress.Security.EscapeOutput
            echo $this->view->renderHeader();
            ?>
        </div>

        <div class="zettle-settings-onboarding-content">
            <?php
            // phpcs:ignore WordPress.Security.EscapeOutput
            echo $this->view->renderContent();
            ?>
        </div>

        <div class="zettle-settings-onboarding-actions">
            <input type="hidden" name="zettle_onboarding_state"
                    value="<?php echo esc_attr($this->currentState); ?>">

            <?php
            // phpcs:ignore WordPress.Security.EscapeOutput
            echo $this->view->renderProceedButton();
            // phpcs:ignore WordPress.Security.EscapeOutput
            echo $this->view->renderBackButton();
            ?>
        </div>

        <?php return (string) ob_get_clean();
    }
}
