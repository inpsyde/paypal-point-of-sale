<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\API\Purchases;

use Psr\Http\Message\UriInterface;
use Syde\PayPal\PointOfSale\PhpSdk\API\Listener\ApiRestListener;
use Syde\PayPal\PointOfSale\PhpSdk\Builder\BuilderInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\Purchase;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\BuilderException;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ZettleRestException;
use Syde\PayPal\PointOfSale\PhpSdk\RestClientInterface;

/**
 * Read-only client for the Zettle Purchase API.
 *
 * @see https://developer.zettle.com/docs/api/purchase
 *
 * @package Syde\PayPal\PointOfSale\PhpSdk\API\Purchases
 */
class Purchases
{
    private UriInterface $uri;

    private RestClientInterface $restClient;

    private BuilderInterface $builder;

    /**
     * @var callable[]
     */
    private array $listeners;

    /**
     * @param UriInterface $uri
     * @param RestClientInterface $restClient
     * @param BuilderInterface $builder
     * @param callable ...$listeners
     */
    public function __construct(
        UriInterface $uri,
        RestClientInterface $restClient,
        BuilderInterface $builder,
        callable ...$listeners
    ) {

        $this->uri = $uri;
        $this->restClient = $restClient;
        $this->builder = $builder;
        $this->listeners = $listeners;
    }

    /**
     * @param string $uuid
     * @param bool $withListeners
     *
     * @return Purchase
     *
     * @throws ZettleRestException
     */
    public function read(string $uuid, bool $withListeners = true): Purchase
    {
        // Singular path: the plural `/purchases/v2` is the list endpoint.
        $url = (string) $this->uri->withPath("/purchase/v2/{$uuid}");
        $result = $this->restClient->get($url, []);

        try {
            $purchase = $this->builder->build(Purchase::class, $result);
        } catch (BuilderException $exception) {
            throw new ZettleRestException(
                sprintf(
                    'Could not read purchase %s',
                    esc_html($uuid)
                ),
                0,
                $result, // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                [],
                $exception // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            );
        }

        if ($withListeners) {
            array_walk(
                $this->listeners,
                static function (callable $listener) use ($purchase): void {
                    $listener(ApiRestListener::READ, $purchase, true);
                }
            );
        }

        return $purchase;
    }
}
