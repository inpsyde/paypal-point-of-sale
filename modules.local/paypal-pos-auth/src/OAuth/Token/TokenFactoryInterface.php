<?php

namespace Syde\PayPal\PointOfSale\Auth\OAuth\Token;

interface TokenFactoryInterface
{

    public function fromArray(array $data): TokenInterface;
}
