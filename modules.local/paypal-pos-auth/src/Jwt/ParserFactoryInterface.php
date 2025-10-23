<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Auth\Jwt;

interface ParserFactoryInterface
{
    public function createParser(): ParserInterface;
}
