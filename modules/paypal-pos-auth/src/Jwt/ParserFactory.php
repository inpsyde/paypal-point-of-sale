<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\Jwt;

class ParserFactory implements ParserFactoryInterface
{
    public function __construct()
    {
    }
    /**
     * @inheritDoc
     */
    public function createParser(): ParserInterface
    {
        return new Parser();
    }
}
