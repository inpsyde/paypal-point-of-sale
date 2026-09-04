<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\OAuth\Grant;

use InvalidArgumentException;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface;
use Syde\Vendor\Zettle\Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\Exception\InvalidTokenException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\Jwt\ParserInterface;
class JwtGrant implements GrantType
{
    public const KEY = 'urn:ietf:params:oauth:grant-type:jwt-bearer';
    private ContainerInterface $credentials;
    private ParserInterface $tokenDecoder;
    private string $clientId;
    /**
     * @param ContainerInterface $credentials
     * @param ParserInterface $tokenDecoder
     * @param string $clientId client_id of the app, see IZET-331
     */
    public function __construct(ContainerInterface $credentials, ParserInterface $tokenDecoder, string $clientId)
    {
        $this->credentials = $credentials;
        $this->tokenDecoder = $tokenDecoder;
        $this->clientId = $clientId;
    }
    /**
     * @inheritDoc
     */
    public function type(): string
    {
        return self::KEY;
    }
    /**
     * @inheritDoc
     */
    public function payload(): array
    {
        try {
            $apiKey = $this->credentials->get('api_key');
            if (empty($apiKey)) {
                throw new InvalidTokenException("No JWT token data provided.", 0);
            }
            $token = $this->tokenDecoder->parse($apiKey);
        } catch (NotFoundExceptionInterface|InvalidArgumentException|RuntimeException $exception) {
            throw new InvalidTokenException('Failed to create JWT token data', 0, $exception);
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }
        return ['assertion' => $apiKey, 'client_id' => $this->clientId];
    }
}
