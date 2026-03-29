<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Onboarding\Settings\FieldRenderer;

use Syde\PayPal\PointOfSale\Settings\FieldRenderer\FieldRendererInterface;
use WC_Settings_API;

/**
 * Does not render the fields, outputs empty string.
 */
class RemovedFieldRenderer implements FieldRendererInterface
{
    public function accepts(string $fieldId, array $fieldConfig, WC_Settings_API $settingsApi): bool
    {
        return ($fieldConfig['zettle_remove'] ?? false);
    }

    public function render(string $fieldId, array $fieldConfig, WC_Settings_API $settingsApi): string
    {
        return '';
    }
}
