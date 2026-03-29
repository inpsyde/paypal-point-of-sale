<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Auth\OAuth\Grant;

use Syde\PayPal\PointOfSale\Auth\Exception\InvalidTokenException;

interface GrantType
{
    /**
     * @return string
     */
    public function type(): string;

    /**
     * @return array
     * @throws InvalidTokenException
     */
    public function payload(): array;
}
