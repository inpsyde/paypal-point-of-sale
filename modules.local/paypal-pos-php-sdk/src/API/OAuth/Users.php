<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\API\OAuth;

use Psr\Http\Message\UriInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ZettleRestException;
use Syde\PayPal\PointOfSale\PhpSdk\RestClientInterface;

class Users
{
    private UriInterface $uri;

    private RestClientInterface $restClient;

    public function __construct(
        UriInterface $uri,
        RestClientInterface $restClient
    ) {

        $this->uri = $uri;
        $this->restClient = $restClient;
    }

    /**
     * @return array
     * @throws ZettleRestException
     */
    public function me(): array
    {
        $url = (string) $this->uri->withPath('/users/me');
        return $this->restClient->get($url, []);
    }
}
