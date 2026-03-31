<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\API\Inventory;

use Psr\Http\Message\UriInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Builder\BuilderInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Location\Location;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\BuilderException;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ZettleRestException;
use Syde\PayPal\PointOfSale\PhpSdk\RestClientInterface;

class Locations
{
    private UriInterface $uri;

    private RestClientInterface $restClient;

    private BuilderInterface $builder;

    public function __construct(
        UriInterface $uri,
        RestClientInterface $restClient,
        BuilderInterface $builder
    ) {

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
