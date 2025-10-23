<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Onboarding\Settings\Filter;

/**
 * The interface allowing to modify settings values during saving.
 */
interface SettingsValueFilter
{
    /**
     * Filters the settings fields.
     * @param array<string, mixed> $settings An array like ['field1' => 'value', 'field2' => 42]
     * @return array<string, mixed>
     */
    public function filterSettingsValues(array $settings): array;
}
