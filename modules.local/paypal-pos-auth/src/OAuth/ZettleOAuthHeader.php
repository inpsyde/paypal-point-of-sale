<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Auth\OAuth;

use Http\Message\Authentication;
use Syde\PayPal\PointOfSale\Auth\Exception\InvalidTokenException;
use Psr\Http\Message\RequestInterface;

class ZettleOAuthHeader implements Authentication
{

    /**
     * @var TokenProviderInterface
     */
    private $tokenStorage;

    public function __construct(TokenProviderInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @inheritDoc
     */
    public function authenticate(RequestInterface $request): RequestInterface
    {
        try {
            $token = $this->tokenStorage->fetch();

            return $request->withHeader('Authorization', "Bearer {$token->access()}");
        } catch (InvalidTokenException $exception) {
            return $request;
        }
    }
}
