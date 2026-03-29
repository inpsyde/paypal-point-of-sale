<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\API\OAuth;

use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ZettleRestException;
use Syde\PayPal\PointOfSale\PhpSdk\RestClientInterface;

class Users
{
    private LoggerInterface $logger;

    private UriInterface $uri;

    private RestClientInterface $restClient;

    public function __construct(
        LoggerInterface $logger,
        UriInterface $uri,
        RestClientInterface $restClient
    ) {

        $this->logger = $logger;
        $this->uri = $uri;
        $this->restClient = $restClient;
    }

    /**
     * @return array
     * @throws ZettleRestException
     * phpcs:disable Inpsyde.CodeQuality.ElementNameMinimalLength.TooShort
     */
    public function me(): array
    {
        $url = (string) $this->uri->withPath('/users/me');
        return $this->restClient->get($url, []);
    }
}
