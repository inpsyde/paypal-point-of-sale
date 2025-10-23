<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Settings\WC;

interface ZettleIntegrationTemplate
{
    /**
     * @return string
     */
    public function render(): string;
}
