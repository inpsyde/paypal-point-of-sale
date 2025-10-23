<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\OAuth;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\OAuth\Token\TokenFactoryInterface;
use Syde\Vendor\Zettle\Psr\Http\Message\ResponseInterface;
class TokenPersistingAuthSuccessHandler implements AuthSuccessHandler
{
    /**
     * @var TokenPersistorInterface
     */
    private $tokenPersistor;
    /**
     * @var TokenFactoryInterface
     */
    private $tokenFactory;
    public function __construct(TokenPersistorInterface $tokenPersistor, TokenFactoryInterface $tokenFactory)
    {
        $this->tokenPersistor = $tokenPersistor;
        $this->tokenFactory = $tokenFactory;
    }
    public function handle(ResponseInterface $response)
    {
        $body = $response->getBody();
        $body->rewind();
        $contents = $body->getContents();
        $json = json_decode($contents, \true);
        $token = $this->tokenFactory->fromArray($json);
        $this->tokenPersistor->persist($token);
    }
}
