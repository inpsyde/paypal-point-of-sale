<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Http\Client\Common;

use Syde\Vendor\Zettle\Http\Client\Exception;
use Syde\Vendor\Zettle\Http\Client\Promise;
use Syde\Vendor\Zettle\Psr\Http\Message\RequestInterface;
use Syde\Vendor\Zettle\Psr\Http\Message\ResponseInterface;
/**
 * Emulates an HTTP Async Client in an HTTP Client.
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
trait HttpAsyncClientEmulator
{
    /**
     * @see HttpClient::sendRequest
     */
    abstract public function sendRequest(RequestInterface $request): ResponseInterface;
    /**
     * @see HttpAsyncClient::sendAsyncRequest
     */
    public function sendAsyncRequest(RequestInterface $request)
    {
        try {
            return new Promise\HttpFulfilledPromise($this->sendRequest($request));
        } catch (Exception $e) {
            return new Promise\HttpRejectedPromise($e);
        }
    }
}
