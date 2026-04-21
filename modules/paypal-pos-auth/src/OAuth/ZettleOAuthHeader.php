<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\OAuth;

use Syde\Vendor\Zettle\Http\Message\Authentication;
use Syde\Vendor\Zettle\Psr\Http\Message\RequestInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\Exception\InvalidTokenException;
class ZettleOAuthHeader implements Authentication
{
    private TokenProviderInterface $tokenStorage;
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
