<?php

namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\OAuth;

use Syde\Vendor\Zettle\Psr\Http\Message\ResponseInterface;
interface AuthSuccessHandler
{
    public function handle(ResponseInterface $response);
}
