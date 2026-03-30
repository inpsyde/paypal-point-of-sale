<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\API\OAuth;

use Syde\Vendor\Zettle\Psr\Http\Message\UriInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Builder\BuilderInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Organization\Organization;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\BuilderException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\ZettleRestException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\RestClientInterface;
class Organizations
{
    private $uri;
    private RestClientInterface $restClient;
    private BuilderInterface $builder;
    /**
     * Organizations constructor.
     *
     * @param UriInterface $uri
     * @param RestClientInterface $restClient
     * @param BuilderInterface $builder
     */
    public function __construct(UriInterface $uri, RestClientInterface $restClient, BuilderInterface $builder)
    {
        $this->uri = $uri;
        $this->restClient = $restClient;
        $this->builder = $builder;
    }
    /**
     * @return Organization
     *
     * @throws ZettleRestException
     */
    public function account(): Organization
    {
        $url = (string) $this->uri->withPath('/api/resources/organizations/self');
        $result = $this->restClient->get($url, []);
        try {
            return $this->builder->build(Organization::class, $result);
        } catch (BuilderException $exception) {
            throw new ZettleRestException(sprintf('Failed to build Organization entity after fetching'), 0, [], [], $exception);
        }
    }
}
