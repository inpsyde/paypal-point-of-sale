<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\Jwt;

class Token implements TokenInterface
{
    /** @var array<string, mixed> */
    protected array $headers;
    /** @var array<string, mixed> */
    protected array $claims;
    protected string $signature;
    /**
     * @param array<string, mixed> $headers
     * @param array<string, mixed> $claims
     * @param string $signature
     */
    public function __construct(array $headers, array $claims, string $signature)
    {
        $this->headers = $headers;
        $this->claims = $claims;
        $this->signature = $signature;
    }
    public function getHeaders(): array
    {
        return $this->headers;
    }
    public function getClaims(): array
    {
        return $this->claims;
    }
}
