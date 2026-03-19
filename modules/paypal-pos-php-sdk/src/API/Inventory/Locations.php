<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\API\Inventory;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Builder\BuilderInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Location\Location;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\BuilderException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\ZettleRestException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\RestClientInterface;
use Syde\Vendor\Zettle\Psr\Http\Message\UriInterface;
class Locations
{
    private $uri;
    /**
     * @var RestClientInterface
     */
    private $restClient;
    /**
     * @var BuilderInterface
     */
    private $builder;
    public function __construct(UriInterface $uri, RestClientInterface $restClient, BuilderInterface $builder)
    {
        $this->uri = $uri;
        $this->restClient = $restClient;
        $this->builder = $builder;
    }
    /**
     * @return Location[]
     * @throws ZettleRestException
     */
    public function all(): array
    {
        $url = (string) $this->uri->withPath('/v3/inventories');
        $result = $this->restClient->get($url, []);
        $locations = [];
        foreach ($result as $locationPayload) {
            try {
                $locations[$locationPayload['inventoryType']] = $this->builder->build(Location::class, $locationPayload);
            } catch (BuilderException $exception) {
                // TODO may wanna log, but an error is pretty unlikely to occur here
            }
        }
        return $locations;
    }
}
