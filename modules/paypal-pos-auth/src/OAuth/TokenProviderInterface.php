<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\OAuth;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\Exception\InvalidTokenException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\OAuth\Token\TokenInterface;
interface TokenProviderInterface
{
    /**
     * @throws InvalidTokenException
     * @return TokenInterface
     */
    public function fetch(): TokenInterface;
}
