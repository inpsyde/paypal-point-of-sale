<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\OAuth;

use Syde\Vendor\Zettle\Psr\Http\Message\ResponseInterface;
interface AuthSuccessHandler
{
    public function handle(ResponseInterface $response): void;
}
