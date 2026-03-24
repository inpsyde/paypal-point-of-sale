<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Auth\OAuth;

use Syde\PayPal\PointOfSale\Auth\Exception\InvalidTokenPropertyException;
use Syde\PayPal\PointOfSale\Auth\OAuth\Token\Token;
use Syde\PayPal\PointOfSale\Auth\OAuth\Token\TokenInterface;
use Psr\Container\ContainerInterface;

/**
 * Class TokenContainer
 *
 * Wraps a Token class so that its data is accessible via the Container interface
 *
 * @package Syde\PayPal\PointOfSale\Auth\OAuth
 */
class TokenDataContainer implements ContainerInterface
{
    /**
     * @var array
     */
    private $accessors;

    /**
     * TokenContainer constructor.
     *
     * @param TokenInterface $token
     */
    public function __construct(TokenInterface $token)
    {
        $this->accessors = [
            'access_token' => static function () use ($token): string {
                return $token->access();
            },
            'refresh_token' => static function () use ($token): string {
                return $token->refresh();
            },
            'expires' => static function () use ($token): int {
                return $token->expires();
            },
        ];
    }

    /**
     * @inheritDoc
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     */
    public function get(string $key)
    {
        if (!$this->has($key)) {
            throw new InvalidTokenPropertyException("Property '{$key}' not found on Token");
        }

        return $this->accessors[$key]();
    }

    /**
     * @inheritDoc
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->accessors);
    }
}
