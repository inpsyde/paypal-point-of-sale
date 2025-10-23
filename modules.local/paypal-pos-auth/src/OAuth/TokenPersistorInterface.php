<?php

namespace Syde\PayPal\PointOfSale\Auth\OAuth;

use Syde\PayPal\PointOfSale\Auth\OAuth\Token\TokenInterface;

interface TokenPersistorInterface
{

    public function persist(TokenInterface $token): bool;

    public function clear(): bool;
}
