<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Http\Client\Common;

use Syde\Vendor\Zettle\Http\Client\HttpAsyncClient;
use Syde\Vendor\Zettle\Http\Client\HttpClient;
/**
 * Emulates a synchronous HTTP client with the help of an asynchronous client.
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
final class EmulatedHttpClient implements HttpClient, HttpAsyncClient
{
    use HttpAsyncClientDecorator;
    use HttpClientEmulator;
    public function __construct(HttpAsyncClient $httpAsyncClient)
    {
        $this->httpAsyncClient = $httpAsyncClient;
    }
}
