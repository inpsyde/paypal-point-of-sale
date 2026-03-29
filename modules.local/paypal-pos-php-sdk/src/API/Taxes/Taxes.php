<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\API\Taxes;

use Psr\Http\Message\UriInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Builder\BuilderInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Tax\TaxRate;
use Syde\PayPal\PointOfSale\PhpSdk\RestClientInterface;

class Taxes
{
    private $baseUri;

    private RestClientInterface $restClient;

    private BuilderInterface $builder;

    public function __construct(
        UriInterface $baseUri,
        RestClientInterface $restClient,
        BuilderInterface $builder
    ) {

        $this->baseUri = $baseUri;
        $this->restClient = $restClient;
        $this->builder = $builder;
    }

    public function all(): array
    {
        $url = (string) $this->baseUri->withPath("/v1/taxes");

        $result = $this->restClient->get($url, []);

        return array_map(function (array $payload): TaxRate {
            return $this->builder->build(TaxRate::class, $payload);
        }, $result['taxRates']);
    }
}
