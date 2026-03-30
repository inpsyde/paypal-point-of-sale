<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\OAuth;

use Syde\Vendor\Zettle\Psr\Container\ContainerInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\Exception\InvalidTokenPropertyException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\OAuth\Token\TokenInterface;
/**
 * Class TokenContainer
 *
 * Wraps a Token class so that its data is accessible via the Container interface
 *
 * @package Syde\PayPal\PointOfSale\Auth\OAuth
 */
class TokenDataContainer implements ContainerInterface
{
    private array $accessors;
    /**
     * TokenContainer constructor.
     *
     * @param TokenInterface $token
     */
    public function __construct(TokenInterface $token)
    {
        $this->accessors = ['access_token' => static function () use ($token): string {
            return $token->access();
        }, 'refresh_token' => static function () use ($token): string {
            return $token->refresh();
        }, 'expires' => static function () use ($token): int {
            return $token->expires();
        }];
    }
    /**
     * @inheritDoc
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
     * phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
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
     * phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->accessors);
    }
}
