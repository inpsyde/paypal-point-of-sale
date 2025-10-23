<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Auth\Jwt;

/**
 * Represents a JWT token.
 */
interface TokenInterface
{
    /**
     * Retrieves the token headers
     *
     * @return array<string, mixed>.
     */
    public function getHeaders(): array;

    /**
     * Retrieves the token claims.
     *
     * @return array<string, mixed>.
     */
    public function getClaims(): array;
}
