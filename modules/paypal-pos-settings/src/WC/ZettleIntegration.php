<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Settings\WC;

use Exception;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Container\WritableContainerInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\OnboardingState as S;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Settings\FieldRenderer\FieldRendererInterface;
use RuntimeException;
use WC_Settings_API;
class ZettleIntegration extends WC_Settings_API
{
    /**
     * @var ZettleIntegrationTemplate
     */
    private $header;
    /**
     * @var string
     */
    private $currentState;
    /**
     * @var FieldRendererInterface[]
     */
    private $renderers;
    /**
     * @var string[]
     */
    private $readonlyFieldTypes = ['title', 'zettle-onboarding'];
    /**
     * @var WritableContainerInterface
     */
    private $container;
    public function __construct(string $id, ZettleIntegrationTemplate $header, string $currentState, array $formFields, callable $isIntegrationPage, WritableContainerInterface $container, FieldRendererInterface ...$renderers)
    {
        $this->header = $header;
        $this->id = $id;
        if (!$this->hasEditableFields($formFields) && $isIntegrationPage()) {
            $GLOBALS['hide_save_button'] = \true;
        }
        $this->init_form_fields();
        // Actions.
        add_action("woocommerce_update_options_integration_{$this->id}", [$this, 'process_admin_options']);
        $this->currentState = $currentState;
        $this->form_fields = $formFields;
        $this->renderers = $renderers;
        $this->container = $container;
    }
    /**
     * @return string
     * phpcs:disable Inpsyde.CodeQuality.NoAccessors.NoGetter
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
     * phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
     */
    public function get_option_key()
    {
        throw new RuntimeException('Things roll a bit different around here');
    }
    /**
     * @param string $key
     * @param mixed|null $emptyValue
     *
     * @return mixed|string
     *
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     */
    public function get_option($key, $emptyValue = null)
    {
        // Get option default if unset.
        if (!$this->container->has($key)) {
            $formFields = $this->get_form_fields();
            $default = isset($formFields[$key]) ? $this->get_field_default($formFields[$key]) : '';
            return $default;
        }
        return $this->container->get($key);
    }
    /**
     * @return bool|void
     * phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
     * phpcs:disable Inpsyde.CodeQuality.NestingLevel.High
     */
    public function process_admin_options()
    {
        $old = [];
        $settings = [];
        $postData = $this->get_post_data();
        foreach ($this->get_form_fields() as $key => $field) {
            if (!isset($postData[$this->get_field_key($key)])) {
                continue;
                // do not reset missing fields
            }
            $old[$key] = $this->get_option($key);
            if ($this->get_field_type($field) !== 'title') {
                try {
                    $settings[$key] = $this->get_field_value($key, $field, $postData);
                } catch (Exception $exception) {
                    $this->add_error($exception->getMessage());
                }
            }
        }
        $sanitized = apply_filters('woocommerce_settings_api_sanitized_fields_' . $this->id, $settings);
        foreach ($sanitized as $key => $value) {
            $this->update_option($key, $value);
        }
        /**
         * Create an array containing only the changed options
         * to make life easier for consuming code.
         * The assumption right now is that no settings can be removed.
         */
        $changed = [];
        foreach ($sanitized as $key => $value) {
            if (!isset($old[$key])) {
                $changed[$key] = $value;
                continue;
            }
            if ($value !== $old[$key]) {
                $changed[$key] = $value;
            }
        }
        do_action('inpsyde.zettle.settings.updated', $changed, $this);
    }
    /**
     * Output the gateway settings screen.
     * @psalm-suppress MissingReturnType
     */
    public function admin_options()
    {
        // phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped
        // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
        echo sprintf('<div class="zettle-settings %s">', $this->currentState === S::ONBOARDING_COMPLETED ? 'is--completed' : '');
        echo $this->header->render();
        echo '<table class="form-table zettle-settings-onboarding">';
        echo $this->generate_settings_html($this->get_form_fields(), \false);
        echo '</table>';
        echo '</div>';
        // phpcs:enable
    }
    /**
     * Mostly copied from the parent method, but allows any field type to be overridden by
     * one of the FieldRenderers
     *
     * @param array $formFields
     * @param bool $echo
     *
     * phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     * phpcs:disable Generic.Metrics.NestingLevel.TooHigh
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
     * phpcs:disable Inpsyde.CodeQuality.NestingLevel.High
     *
     * @return string
     */
    public function generate_settings_html($formFields = [], $echo = \true)
    {
        if (empty($formFields)) {
            $formFields = $this->get_form_fields();
        }
        $html = '';
        foreach ($formFields as $fieldId => $fieldConfig) {
            $type = $this->get_field_type($fieldConfig);
            foreach ($this->renderers as $renderer) {
                if ($renderer->accepts($fieldId, $fieldConfig, $this)) {
                    $html .= $renderer->render($fieldId, $fieldConfig, $this);
                    continue 2;
                }
            }
            if (method_exists($this, 'generate_' . $type . '_html')) {
                $html .= $this->{'generate_' . $type . '_html'}($fieldId, $fieldConfig);
                continue;
            }
            $html .= $this->generate_text_html($fieldId, $fieldConfig);
        }
        if ($echo) {
            // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $html;
            // phpcs:enable
        }
        return $html;
    }
    /** Checks if the form field is visible and can be edited by user.
     *
     * @param array $formField WC form fields config.
     *
     * @return bool
     */
    private function isEditableField(array $formField): bool
    {
        $isHidden = $formField['zettle_hide'] ?? \false;
        $isEditable = !in_array($this->get_field_type($formField), $this->readonlyFieldTypes, \true);
        return $isEditable && !$isHidden;
    }
    /** Checks if the form fields contain any fields that are visible and can be edited by user.
     *
     * @param array $formFields WC form fields config.
     *
     * @return bool
     */
    private function hasEditableFields(array $formFields): bool
    {
        foreach ($formFields as $field) {
            if ($this->isEditableField($field)) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * Update a single option.
     *
     * @param string $key Option key.
     * @param mixed $value Value to set.
     *
     * @return bool was anything saved?
     * @since 3.4.0
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
     * phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
     */
    public function update_option($key, $value = '')
    {
        $this->container->set($key, $value);
        return \true;
    }
}
