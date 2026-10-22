<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\OAuth\Token;

class Token implements TokenInterface
{
    private string $accessToken;
    private int $expiresIn;
    private string $refreshToken;
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
        $data = ['access_token' => $this->access(), 'expires_in' => $this->expires()];
        if (!empty($this->refresh())) {
            $data['refresh_token'] = $this->refresh();
        }
        return $data;
    }
}
