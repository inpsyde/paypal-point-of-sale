<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\Settings\Filter;

interface SettingsFilter
{
    /**
     * @param array $settings
     *
     * @return array
     */
    public function filter(array $settings): array;
}
