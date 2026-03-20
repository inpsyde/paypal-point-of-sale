<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\OAuth;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\AuthenticatedClientFactory;
use Syde\Vendor\Zettle\Psr\Http\Message\RequestFactoryInterface;
use Throwable;
class CredentialValidator
{
    const ENDPOINT = 'https://oauth.izettle.com/users/me';
    /**
     * @var AuthenticatedClientFactory
     */
    private $clientFactory;
    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;
    public function __construct(AuthenticatedClientFactory $clientFactory, RequestFactoryInterface $requestFactory)
    {
        $this->clientFactory = $clientFactory;
        $this->requestFactory = $requestFactory;
    }
    public function validateApiToken(string $token): bool
    {
        try {
            $this->clientFactory->withApiToken($token)->sendRequest($this->requestFactory->createRequest('GET', self::ENDPOINT));
            return \true;
        } catch (Throwable $exception) {
            /**
             * Any kind of error here means the request did not succeed.
             * While this is not strictly a check for authentication errors,
             * it also means we have _some_ problem with creating authenticated requests, so
             * it would be unwise to signal success here for other error scenarios.
             */
            return \false;
        }
    }
}
