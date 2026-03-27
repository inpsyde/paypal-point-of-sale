<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\OAuth;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\Exception\InvalidTokenException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Container\WritableContainerInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\OAuth\Token\TokenFactoryInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\OAuth\Token\TokenInterface;
/**
 * Stores Zettle access tokens in a child container.
 * We kind of expect the child container to offer some persistence...
 */
class ContainerTokenStorage implements TokenPersistorInterface, TokenProviderInterface
{
    /**
     * @var WritableContainerInterface
     */
    private $container;
    /**
     * @var string
     */
    private $key;
    /**
     * @var TokenFactoryInterface
     */
    private $tokenFactory;
    /**
     * SiteOptionTokenStorage constructor.
     *
     * @param WritableContainerInterface $container
     * @param string $key
     * @param TokenFactoryInterface $tokenFactory
     */
    public function __construct(WritableContainerInterface $container, string $key, TokenFactoryInterface $tokenFactory)
    {
        $this->container = $container;
        $this->key = $key;
        $this->tokenFactory = $tokenFactory;
    }
    /**
     * @return TokenInterface
     *
     * @throws InvalidTokenException
     */
    public function fetch(): TokenInterface
    {
        if (!$this->container->has($this->key)) {
            throw new InvalidTokenException('Token was not found in the database');
        }
        $tokenData = $this->container->get($this->key);
        $tokenData = json_decode($tokenData, \true);
        if (!$tokenData) {
            throw new InvalidTokenException('Token could not be decoded');
        }
        return $this->tokenFactory->fromArray($tokenData);
    }
    public function persist(TokenInterface $token): bool
    {
        $this->container->set($this->key, json_encode($token->toArray()));
        return \true;
    }
    public function clear(): bool
    {
        $this->container->unset($this->key);
        return \true;
    }
}
