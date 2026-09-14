<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Onboarding\Settings\FieldRenderer;

use Syde\PayPal\PointOfSale\Settings\FieldRenderer\FieldRendererInterface;
use WC_Settings_API;

class HiddenFieldRenderer implements FieldRendererInterface
{
    public function accepts(string $fieldId, array $fieldConfig, WC_Settings_API $settingsApi): bool
    {
        return isset($fieldConfig['zettle_hide']);
    }

    public function render(string $fieldId, array $fieldConfig, WC_Settings_API $settingsApi): string
    {
        unset($fieldConfig['zettle_hide']);

        switch ($fieldConfig['type']) {
            case 'title':
                return '';
            default:
                return $this->renderDefault($fieldId, $fieldConfig, $settingsApi);
        }
    }

    private function renderDefault(
        string $fieldId,
        array $fieldConfig,
        WC_Settings_API $settingsApi
    ): string {

        ob_start();
        $settingsApi->generate_settings_html([$fieldId => $fieldConfig], true);

        return str_replace(
            '<tr valign="top">',
            '<tr valign="top" style="display:none">',
            (string) ob_get_clean()
        );
    }
}
