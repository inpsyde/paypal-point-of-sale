<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Http\Client\Common;

use Syde\Vendor\Zettle\Http\Client\Common\HttpClientPool\HttpClientPoolItem;
use Syde\Vendor\Zettle\Http\Client\HttpAsyncClient;
use Syde\Vendor\Zettle\Http\Client\HttpClient;
use Syde\Vendor\Zettle\Psr\Http\Client\ClientInterface;
/**
 * A http client pool allows to send requests on a pool of different http client using a specific strategy (least used,
 * round robin, ...).
 */
interface HttpClientPool extends HttpAsyncClient, HttpClient
{
    /**
     * Add a client to the pool.
     *
     * @param ClientInterface|HttpAsyncClient|HttpClientPoolItem $client
     */
    public function addHttpClient($client): void;
}
