<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Auth\OAuth\Token;

use Syde\PayPal\PointOfSale\Auth\Exception\InvalidTokenException;
use stdClass;

class Token implements TokenInterface
{

    /**
     * @var string
     */
    private $accessToken;

    /**
     * @var int
     */
    private $expiresIn;

    /**
     * @var string
     */
    private $refreshToken;

    /**
     * JwtToken constructor.
     *
     * @param string $accessToken
     * @param int $expiresIn
     * @param string $refreshToken
     */
    public function __construct(string $accessToken, int $expiresIn, string $refreshToken = '')
    {
        $this->accessToken = $accessToken;
        $this->expiresIn = $expiresIn;
        $this->refreshToken = $refreshToken;
    }

    /**
     * @return string
     */
    public function access(): string
    {
        return $this->accessToken;
    }

    /**
     * @return int
     */
    public function expires(): int
    {
        return $this->expiresIn;
    }

    public function refresh(): string
    {
        return $this->refreshToken;
    }

    public function toArray(): array
    {
        $data = [
            'access_token' => $this->access(),
            'expires_in' => $this->expires(),
        ];

        if (!empty($this->refresh())) {
            $data['refresh_token'] = $this->refresh();
        }

        return $data;
    }
}
