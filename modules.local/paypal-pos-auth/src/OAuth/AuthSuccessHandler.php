<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Auth\OAuth;

use Psr\Http\Message\ResponseInterface;

interface AuthSuccessHandler
{
    public function handle(ResponseInterface $response);
}
