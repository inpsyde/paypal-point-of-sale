<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\Http;

use Syde\Vendor\Zettle\Http\Client\Common\Plugin;
use Syde\Vendor\Zettle\Http\Client\Common\PluginClient;
use Syde\Vendor\Zettle\Psr\Http\Client\ClientInterface;
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
        return new PluginClient($this->innerClient, $plugins);
    }
}
