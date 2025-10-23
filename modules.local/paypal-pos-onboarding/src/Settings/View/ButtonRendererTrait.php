<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Onboarding\Settings\View;

use Syde\PayPal\PointOfSale\Onboarding\Settings\ButtonAction;
use Syde\PayPal\PointOfSale\Onboarding\Settings\ButtonKind;

trait ButtonRendererTrait
{
    /**
     * Generates the specified action button HTML.
     *
     * @param string $action The value for the name attribute, should be from ButtonAction constants.
     * @param string|null $label The button text, or null to use default text based on the action.
     * @param array{kind?: string, disabled?: bool, hidden?: bool, type?: string, value?: string, attributes?: array<string, string>}
     * The array with additional parameters.
     * 'kind' - button type/CSS class from ButtonKind, if omitted - using default based on the action.
     * 'disabled' - true/false, whether to add `disabled` attribute (default false).
     * 'hidden' - true/false, whether to make it hidden (default false).
     * 'type' - HTML button type (default submit).
     * 'value' - HTML button value (default "Save changes").
     * 'attributes' - additional HTML attributes, such as data-*.
     * @return string
     */
    protected function renderActionButton(
        string $action,
        ?string $label = null,
        array $params = []
    ): string {
        if ($label === null) {
            $label = $this->getDefaultButtonLabel($action);
        }

        $kind = $params['kind'] ?? $this->getDefaultButtonKind($action);
        $disabled = $params['disabled'] ?? false;
        $hidden = $params['hidden'] ?? false;
        $type = $params['type'] ?? 'submit';
        $value = $params['value'] ?? __('Save changes', 'woocommerce');
        $attributes = $params['attributes'] ?? [];

        $attributesHtml = implode(' ', array_map(
            /** @psalm-suppress ReservedWord psalm bug? */
            static function (string $key) use ($attributes): string {
                return sprintf('%1$s="%2$s"', esc_html($key), esc_attr($attributes[$key]));
            },
            array_keys($attributes)
        ));

        ob_start(); ?>

        <button name="<?php echo esc_attr($action); ?>"
                class="btn btn-<?php echo esc_attr($kind); ?>"
                <?php echo $disabled ? 'disabled' : ''; ?>
                style="<?php echo $hidden ? 'display: none;' : ''; ?>"
                type="<?= esc_attr($type) ?>"
                value="<?= esc_attr($value) ?>"
                <?= $attributesHtml // WPCS: xss ok. ?>>
            <?php echo esc_attr($label); ?>
        </button>

        <?php return ob_get_clean();
    }

    protected function getDefaultButtonLabel(string $action): string
    {
        switch ($action) {
            case ButtonAction::BACK:
                return __('Back', 'paypal-point-of-sale');
            case ButtonAction::PROCEED:
                return __('Next', 'paypal-point-of-sale');
            default:
                return '';
        }
    }

    protected function getDefaultButtonKind(string $action): string
    {
        switch ($action) {
            case ButtonAction::BACK:
                return ButtonKind::SECONDARY;
            case ButtonAction::PROCEED:
                return ButtonKind::PRIMARY;
            case ButtonAction::DELETE:
                return ButtonKind::DELETE;
            default:
                return '';
        }
    }
}
