<?php

namespace Syde\Vendor\Zettle\Http\Discovery\Strategy;

use Syde\Vendor\Zettle\Http\Client\HttpAsyncClient;
use Syde\Vendor\Zettle\Http\Client\HttpClient;
use Syde\Vendor\Zettle\Http\Mock\Client as Mock;
/**
 * Find the Mock client.
 *
 * @author Sam Rapaport <me@samrapdev.com>
 */
final class MockClientStrategy implements DiscoveryStrategy
{
    public static function getCandidates($type)
    {
        if (is_a(HttpClient::class, $type, \true) || is_a(HttpAsyncClient::class, $type, \true)) {
            return [['class' => Mock::class, 'condition' => Mock::class]];
        }
        return [];
    }
}
