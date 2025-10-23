<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Auth\Jwt;

use InvalidArgumentException;
use RuntimeException;

interface ParserInterface
{
    /**
     * Parses the JWT and returns a token
     *
     * @param string $jwt The token to parse.
     *
     * @return TokenInterface The token.
     *
     * @throws InvalidArgumentException  When JWT is not a string or is invalid.
     * @throws RuntimeException          When something goes wrong while decoding
     */
    public function parse(string $jwt): TokenInterface;
}
