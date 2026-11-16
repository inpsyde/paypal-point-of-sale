<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\OAuth;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\OAuth\Token\TokenInterface;
interface TokenPersistorInterface
{
    public function persist(TokenInterface $token): bool;
    public function clear(): bool;
}
