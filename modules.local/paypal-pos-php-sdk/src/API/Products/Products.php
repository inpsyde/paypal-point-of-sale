<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\API\Products;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use Syde\PayPal\PointOfSale\Auth\Exception\AuthenticationException;
use Syde\PayPal\PointOfSale\PhpSdk\API\Listener\ApiRestListener;
use Syde\PayPal\PointOfSale\PhpSdk\Builder\BuilderInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\Product;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\ProductCollection;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Product\ProductInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\BuilderException;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ZettleRestException;
use Syde\PayPal\PointOfSale\PhpSdk\RestClientInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Serializer\SerializerInterface;

/**
 * Class Products
 *
 * Provides abstractions for the 'products' resource of the Zettle Api
 *
 * @see https://products.izettle.com/swagger#/products
 *
 * @package Syde\PayPal\PointOfSale\PhpSdk\API\Products
 */
class Products
{
    private UriInterface $uri;

    private RestClientInterface $restClient;

    /**
     * @var callable[]
     */
    private array $listeners;

    private BuilderInterface $builder;

    private SerializerInterface $serializer;

    /**
     * Products constructor.
     *
     * @param UriInterface $uri
     * @param RestClientInterface $restClient
     * @param BuilderInterface $builder
     * @param SerializerInterface $serializer
     * @param callable ...$listeners
     */
    public function __construct(
        UriInterface $uri,
        RestClientInterface $restClient,
        BuilderInterface $builder,
        SerializerInterface $serializer,
        callable ...$listeners
    ) {

        $this->uri = $uri;
        $this->restClient = $restClient;
        $this->builder = $builder;
        $this->serializer = $serializer;
        $this->listeners = $listeners;
    }

    /**
     * @param bool $withListeners
     *
     * @return ProductCollection
     *
     * @throws ZettleRestException
     * phpcs:disable Generic.Metrics.NestingLevel.TooHigh
     */
    public function list(bool $withListeners = true): ProductCollection
    {
        $url = (string) $this->uri->withPath('/organizations/self/products/v2');

        $result = $this->restClient->get($url, []);
        try {
            $collection = $this->builder->build(ProductCollection::class, $result);
        } catch (BuilderException $exception) {
            throw new ZettleRestException(
                'Failed to build product collection',
                0,
                $result, // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                [],
                $exception // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            );
        }

        $products = $collection->all();

        if ($withListeners) {
            array_walk(
                $products,
                function (ProductInterface $product) {
                    array_walk(
                        $this->listeners,
                        static function (callable $listener) use ($product) {
                            $listener(ApiRestListener::READ, $product, true);
                        }
                    );
                }
            );
        }

        return $collection;
    }

    /**
     * @param ProductInterface $product
     * @param bool $withListeners
     *
     * @return Product
     *
     * @throws ZettleRestException
     */
    public function create(ProductInterface $product, bool $withListeners = true): ProductInterface
    {
        $url = (string) $this->uri->withPath('/organizations/self/products')
            ->withQuery('returnEntity=true');

        $payload = $this->serializer->serialize($product);
        $success = true;

        $result = $this->restClient->post($url, $payload);
        try {
            $created = $this->builder->build(ProductInterface::class, $result);
        } catch (BuilderException $exception) {
            throw new ZettleRestException(
                sprintf(
                    'Failed to build product %s',
                    esc_html($product->uuid())
                ),
                0,
                $result, // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                $payload, // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                $exception // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            );
        }

        if ($withListeners) {
            array_walk(
                $this->listeners,
                static function (callable $listener) use ($created, $success) {
                    $listener(ApiRestListener::CREATE, $created, $success);
                }
            );
        }

        return $created;
    }

    /**
     * @param string $uuid
     * @param bool $withListeners
     *
     * @return ProductInterface
     *
     * @throws ZettleRestException
     */
    public function read(string $uuid, bool $withListeners = true): ProductInterface
    {
        $url = (string) $this->uri->withPath("/organizations/self/products/{$uuid}");
        $result = $this->restClient->get($url, []);

        try {
            $product = $this->builder->build(ProductInterface::class, $result);
        } catch (BuilderException $exception) {
            throw new ZettleRestException(
                sprintf(
                    'Could not read product %s',
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
                static function (callable $listener) use ($product) {
                    $listener(ApiRestListener::READ, $product, true);
                }
            );
        }

        return $product;
    }

    /**
     * @param ProductInterface $product
     * @param bool $withListeners
     *
     * @return ProductInterface
     *
     * @throws ZettleRestException
     */
    public function update(ProductInterface $product, bool $withListeners = true): ProductInterface
    {
        $url = (string) $this->uri->withPath("/organizations/self/products/v2/{$product->uuid()}");

        $success = true;

        $existingProduct = $this->read((string) $product->uuid());

        $etag = $existingProduct->etag();
        $product->setEtag($etag);

        $payload = $this->serializer->serialize($product);

        try {
            $result = $this->restClient->put(
                $url,
                $payload,
                static function (RequestInterface $request) use ($etag): RequestInterface {
                    return $request->withHeader('If-Match', '"' . $etag . '"');
                }
            );

            $product = $this->builder->build(ProductInterface::class, $result);
        } catch (BuilderException | AuthenticationException $exception) {
            throw new ZettleRestException(
                sprintf(
                    'Failed to build product %s after updating',
                    esc_html($product->uuid())
                ),
                0,
                $result ?? [], // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                $payload, // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                $exception // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            );
        }

        if ($withListeners) {
            array_walk(
                $this->listeners,
                static function (callable $listener) use ($product, $success) {
                    $listener(ApiRestListener::UPDATE, $product, $success);
                }
            );
        }

        return $product;
    }

    /**
     * @param string $uuid
     * @param bool $withListeners
     *
     * @return bool
     * @throws ZettleRestException
     */
    public function delete(string $uuid, bool $withListeners = true): bool
    {
        $url = (string) $this->uri->withPath("/organizations/self/products/{$uuid}");

        $success = true;

        $this->restClient->delete($url, []);

        if ($withListeners) {
            array_walk(
                $this->listeners,
                static function (callable $listener) use ($uuid, $success) {
                    $listener(ApiRestListener::DELETE, $uuid, $success);
                }
            );
        }

        return $success;
    }

    /**
     * @param ProductInterface[] $products
     * @param bool $withListeners
     *
     * @return bool
     * @throws ZettleRestException
     *
     * phpcs:disable Generic.Metrics.NestingLevel.TooHigh
     */
    public function deleteBulk(array $products, bool $withListeners = true): bool
    {
        if (empty($products)) {
            return true;
        }

        $uuids = array_map(
            static function (ProductInterface $product): string {
                return $product->uuid();
            },
            $products
        );

        $queryString = implode('&uuid=', $uuids);

        $url = (string) $this->uri->withPath("/organizations/self/products")
            ->withQuery("uuid={$queryString}");

        $success = true;

        $this->restClient->delete($url, []);

        if ($withListeners) {
            foreach ($products as $product) {
                foreach ($this->listeners as $listener) {
                    $listener(ApiRestListener::DELETE, $product, $success);
                }
            }
        }

        return $success;
    }
}
