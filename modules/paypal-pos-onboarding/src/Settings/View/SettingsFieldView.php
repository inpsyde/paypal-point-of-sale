<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\Settings\View;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\Settings\ButtonAction;
use WC_Settings_API;
class SettingsFieldView implements OnboardingView
{
    use ButtonRendererTrait;
    protected WC_Settings_API $settingsApi;
    protected string $title;
    protected string $content;
    protected array $fields;
    protected string $notice;
    protected array $allowedFieldTags;
    /**
     * CredentialAwareView constructor.
     *
     * @param WC_Settings_API $settingsApi
     * @param string $title
     * @param string $content
     * @param array $fields
     * @param string $notice
     * @param array $allowedFieldTags
     */
    public function __construct(WC_Settings_API $settingsApi, string $title, string $content, array $fields, string $notice = '', array $allowedFieldTags = [])
    {
        $this->settingsApi = $settingsApi;
        $this->title = $title;
        $this->content = $content;
        $this->fields = $fields;
        $this->notice = $notice;
        $this->allowedFieldTags = !empty($allowedFieldTags) ? $allowedFieldTags : ['label', 'span', 'fieldset', 'legend', 'input'];
    }
    public function renderHeader(): string
    {
        ob_start();
        ?>

        <h2>
            <?php 
        echo esc_html($this->title);
        ?>
        </h2>

        <p>
            <?php 
        echo wp_kses_post($this->content);
        ?>
        </p>

        <?php 
        return ob_get_clean();
    }
    public function renderContent(): string
    {
        ob_start();
        if (!empty($this->notice)) {
            ?>
            <p>
                <strong><?php 
            echo esc_html($this->notice);
            ?></strong>
            </p>
        <?php 
        }
        ?>

        <div class="zettle-settings-onboarding-fields">
            <?php 
        foreach ($this->fields as $fieldId) {
            if (isset($this->settingsApi->form_fields[$fieldId])) {
                $fieldConfig = $this->settingsApi->form_fields[$fieldId];
                unset($fieldConfig['custom_attributes'], $fieldConfig['zettle_hide']);
                $field = $this->settingsApi->generate_settings_html([$fieldId => $fieldConfig], \false);
                $strippedField = strip_tags($field, sprintf('<%s>', implode('><', $this->allowedFieldTags)));
                echo '<div class="zettle-client-id field-row">';
                // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
                echo $strippedField;
                // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
                echo '</div>';
            }
        }
        ?>
        </div>

        <?php 
        return ob_get_clean();
    }
    public function renderProceedButton(): string
    {
        return $this->renderActionButton(ButtonAction::PROCEED);
    }
    public function renderBackButton(): string
    {
        return $this->renderActionButton(ButtonAction::BACK);
    }
}
