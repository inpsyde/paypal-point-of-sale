<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Auth\OAuth;

use Syde\PayPal\PointOfSale\Auth\Exception\InvalidTokenException;
use Syde\PayPal\PointOfSale\Auth\OAuth\Token\TokenInterface;

interface TokenProviderInterface
{
    /**
     * @throws InvalidTokenException
     * @return TokenInterface
     */
    public function fetch(): TokenInterface;
}
