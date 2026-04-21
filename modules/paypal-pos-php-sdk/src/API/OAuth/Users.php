<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\API\OAuth;

use Syde\Vendor\Zettle\Psr\Http\Message\UriInterface;
use Syde\Vendor\Zettle\Psr\Log\LoggerInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\ZettleRestException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\RestClientInterface;
class Users
{
    private LoggerInterface $logger;
    private UriInterface $uri;
    private RestClientInterface $restClient;
    public function __construct(LoggerInterface $logger, UriInterface $uri, RestClientInterface $restClient)
    {
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
