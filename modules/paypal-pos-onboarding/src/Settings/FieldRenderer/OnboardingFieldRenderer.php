<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\Settings\FieldRenderer;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\OnboardingState as S;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\Settings\OnboardingStepper;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\Settings\View\OnboardingView;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Settings\FieldRenderer\FieldRendererInterface;
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
    public function __construct(string $currentState, OnboardingView $view, OnboardingStepper $stepper)
    {
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
    public function render(string $fieldId, array $fieldConfig, WC_Settings_API $settingsApi): string
    {
        do_action('inpsyde.zettle.onboarding.rendering-started');
        $fieldKey = $settingsApi->get_field_key($fieldId);
        $fieldConfig = array_merge(['title' => '', 'disabled' => \false, 'class' => '', 'css' => '', 'placeholder' => '', 'type' => 'text', 'desc_tip' => \false, 'description' => '', 'custom_attributes' => []], $fieldConfig);
        ob_start();
        ?>

        <tr valign="top">
            <th scope="row" class="titledesc">
                <?php 
        echo $this->renderTableHead($fieldKey, $fieldConfig, $settingsApi);
        // phpcs:ignore WordPress.Security.EscapeOutput 
        ?>
            </th>

            <td class="forminp">
                <div class="zettle-settings-onboarding-container">
                    <?php 
        echo $this->renderTableContent();
        // phpcs:ignore WordPress.Security.EscapeOutput 
        ?>
                </div>
            </td>
        </tr>

        <?php 
        return (string) ob_get_clean();
    }
    /**
     * @param string $fieldKey
     * @param array $fieldConfig
     * @param WC_Settings_API $settingsApi
     *
     * @return string
     */
    protected function renderTableHead(string $fieldKey, array $fieldConfig, WC_Settings_API $settingsApi): string
    {
        ob_start();
        ?>

        <div class="zettle-settings-onboarding-caption">
            <div class="zettle-settings-onboarding-caption-title">
                <?php 
        if ($this->currentState !== S::WELCOME) {
            ?>
                    <label for="<?php 
            echo esc_attr($fieldKey);
            ?>">
                        <?php 
            echo wp_kses_post($fieldConfig['title']);
            echo $settingsApi->get_tooltip_html($fieldConfig);
            // phpcs:ignore WordPress.Security.EscapeOutput
            ?>
                    </label>
                <?php 
        }
        ?>
            </div>

            <?php 
        if ($this->stepper->canRender()) {
            ?>
                <div class="zettle-settings-onboarding-caption-stepper">
                    <?php 
            echo wp_kses_post($this->stepper->render());
            ?>
                </div>
            <?php 
        }
        ?>
        </div>

        <?php 
        return (string) ob_get_clean();
    }
    /**
     * @return string
     */
    protected function renderTableContent(): string
    {
        ob_start();
        ?>

        <div class="zettle-settings-onboarding-header">
            <?php 
        echo $this->view->renderHeader();
        // phpcs:ignore WordPress.Security.EscapeOutput 
        ?>
        </div>

        <div class="zettle-settings-onboarding-content">
            <?php 
        echo $this->view->renderContent();
        // phpcs:ignore WordPress.Security.EscapeOutput 
        ?>
        </div>

        <div class="zettle-settings-onboarding-actions">
            <input type="hidden" name="zettle_onboarding_state"
                    value="<?php 
        echo esc_attr($this->currentState);
        ?>">

            <?php 
        echo $this->view->renderProceedButton();
        // phpcs:ignore WordPress.Security.EscapeOutput
        echo $this->view->renderBackButton();
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>
        </div>

        <?php 
        return (string) ob_get_clean();
    }
}
