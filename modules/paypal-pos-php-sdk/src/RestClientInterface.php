<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\ZettleRestException;
interface RestClientInterface
{
    /**
     * @param string $url
     * @param array $payload
     * @param callable|null $modifyRequest
     *
     * @return array
     *
     * @throws ZettleRestException
     */
    public function get(string $url, array $payload, ?callable $modifyRequest = null): array;
    /**
     * @param string $url
     * @param array $payload
     * @param callable|null $modifyRequest
     *
     * @return array
     *
     * @throws ZettleRestException
     */
    public function post(string $url, array $payload, ?callable $modifyRequest = null): array;
    /**
     * @param string $url
     * @param array $payload
     * @param callable|null $modifyRequest
     *
     * @return array
     *
     * @throws ZettleRestException
     */
    public function put(string $url, array $payload, ?callable $modifyRequest = null): array;
    /**
     * @param string $url
     * @param array $payload
     * @param callable|null $modifyRequest
     *
     * @return array
     *
     * @throws ZettleRestException
     */
    public function delete(string $url, array $payload, ?callable $modifyRequest = null): array;
}
