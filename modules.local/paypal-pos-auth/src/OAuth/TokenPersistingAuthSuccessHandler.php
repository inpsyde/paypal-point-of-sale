<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Auth\OAuth;

use Psr\Http\Message\ResponseInterface;
use Syde\PayPal\PointOfSale\Auth\OAuth\Token\TokenFactoryInterface;

class TokenPersistingAuthSuccessHandler implements AuthSuccessHandler
{
    private TokenPersistorInterface $tokenPersistor;

    private TokenFactoryInterface $tokenFactory;

    public function __construct(
        TokenPersistorInterface $tokenPersistor,
        TokenFactoryInterface $tokenFactory
    ) {

        $this->tokenPersistor = $tokenPersistor;
        $this->tokenFactory = $tokenFactory;
    }

    public function handle(ResponseInterface $response): void
    {
        $body = $response->getBody();
        $body->rewind();

        $contents = $body->getContents();
        $json = json_decode($contents, true);
        $token = $this->tokenFactory->fromArray($json);
        $this->tokenPersistor->persist($token);
    }
}
