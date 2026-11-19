<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Adapter\Unleash\Http;

use Syde\Vendor\Zettle\Psr\Http\Client\ClientInterface;
use Syde\Vendor\Zettle\Psr\Http\Message\RequestInterface;
use Syde\Vendor\Zettle\Psr\Http\Message\ResponseInterface;
/**
 * Wraps the SDK's HTTP client and turns a successful flag fetch without a flag list, e.g. from
 * a wrong URL that answers with other JSON, into a client error.
 *
 * The SDK would store such a response as its stale copy, replacing a good one, and fail only
 * afterwards, without caching anything. As a client error, the fetch counts as failed: the
 * stale copy stays, and the stale copy or the defaults are used for the fresh TTL.
 *
 * @internal Built by UnleashProjectBuilder.
 */
final class FeatureResponseCheck implements ClientInterface
{
    private const FEATURES_PATH = '/client/features';
    public function __construct(private readonly ClientInterface $client)
    {
    }
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $response = $this->client->sendRequest($request);
        if ($response->getStatusCode() !== 200 || !str_ends_with(rtrim($request->getUri()->getPath(), '/'), self::FEATURES_PATH)) {
            return $response;
        }
        $body = json_decode((string) $response->getBody(), \true);
        if (!is_array($body) || !is_array($body['features'] ?? null)) {
            throw new InvalidFeatureResponse($request, 'The response from Unleash contains no flag list. Check the Unleash URL.');
        }
        return $response;
    }
}
