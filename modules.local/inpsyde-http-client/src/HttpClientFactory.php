<?php

declare(strict_types=1);

namespace Inpsyde\Http;

use Http\Client\Common\Plugin;
use Http\Client\Common\PluginClient;
use Psr\Http\Client\ClientInterface;

class HttpClientFactory
{
    /**
     * @var ClientInterface
     */
    protected $innerClient;

    public function __construct(ClientInterface $innerClient)
    {
        $this->innerClient = $innerClient;
    }

    public function withPlugins(Plugin ...$plugins): ClientInterface
    {
        return new PluginClient(
            $this->innerClient,
            $plugins
        );
    }
}
