<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\OAuth\Token;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\Exception\InvalidTokenException;
class TokenFactory implements TokenFactoryInterface
{
    /**
     * @param array $data
     *
     * @return TokenInterface
     * @throws InvalidTokenException
     */
    public function fromArray(array $data): TokenInterface
    {
        if (count($data) < 2) {
            throw new InvalidTokenException('Invalid token data.');
        }
        foreach (['access_token', 'expires_in'] as $required) {
            if (!array_key_exists($required, $data)) {
                throw new InvalidTokenException(sprintf('Property %s not found in received token data', esc_html($required)));
            }
        }
        $refreshToken = $data['refresh_token'] ?? '';
        return new Token($data['access_token'], $data['expires_in'], $refreshToken);
    }
}
