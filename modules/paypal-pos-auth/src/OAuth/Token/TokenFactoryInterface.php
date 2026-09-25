<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\OAuth\Token;

interface TokenFactoryInterface
{
    public function fromArray(array $data): TokenInterface;
}
