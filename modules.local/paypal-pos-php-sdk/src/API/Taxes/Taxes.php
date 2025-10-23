<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\API\Taxes;

use Syde\PayPal\PointOfSale\PhpSdk\Builder\BuilderInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Tax\TaxRate;
use Syde\PayPal\PointOfSale\PhpSdk\RestClientInterface;
use Psr\Http\Message\UriInterface;

class Taxes
{
    private $baseUri;

    /**
     * @var RestClientInterface
     */
    private $restClient;

    /**
     * @var BuilderInterface
     */
    private $builder;

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
