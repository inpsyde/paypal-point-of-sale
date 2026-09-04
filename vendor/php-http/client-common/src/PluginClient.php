<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Http\Client\Common;

use Syde\Vendor\Zettle\Http\Client\Exception as HttplugException;
use Syde\Vendor\Zettle\Http\Client\HttpAsyncClient;
use Syde\Vendor\Zettle\Http\Client\HttpClient;
use Syde\Vendor\Zettle\Http\Client\Promise\HttpFulfilledPromise;
use Syde\Vendor\Zettle\Http\Client\Promise\HttpRejectedPromise;
use Syde\Vendor\Zettle\Http\Promise\Promise;
use Syde\Vendor\Zettle\Psr\Http\Client\ClientInterface;
use Syde\Vendor\Zettle\Psr\Http\Message\RequestInterface;
use Syde\Vendor\Zettle\Psr\Http\Message\ResponseInterface;
use Syde\Vendor\Zettle\Symfony\Component\OptionsResolver\OptionsResolver;
/**
 * The client managing plugins and providing a decorator around HTTP Clients.
 *
 * @author Joel Wurtz <joel.wurtz@gmail.com>
 */
final class PluginClient implements HttpClient, HttpAsyncClient
{
    /**
     * An HTTP async client.
     *
     * @var HttpAsyncClient
     */
    private $client;
    /**
     * The plugin chain.
     *
     * @var Plugin[]
     */
    private $plugins;
    /**
     * A list of options.
     *
     * @var array
     */
    private $options;
    /**
     * @param ClientInterface|HttpAsyncClient $client  An HTTP async client
     * @param Plugin[]                        $plugins A plugin chain
     * @param array{'max_restarts'?: int}     $options
     */
    public function __construct($client, array $plugins = [], array $options = [])
    {
        if ($client instanceof HttpAsyncClient) {
            $this->client = $client;
        } elseif ($client instanceof ClientInterface) {
            $this->client = new EmulatedHttpAsyncClient($client);
        } else {
            throw new \TypeError(sprintf('%s::__construct(): Argument #1 ($client) must be of type %s|%s, %s given', self::class, ClientInterface::class, HttpAsyncClient::class, get_debug_type($client)));
        }
        $this->plugins = $plugins;
        $this->options = $this->configure($options);
    }
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        // If the client doesn't support sync calls, call async
        if (!$this->client instanceof ClientInterface) {
            return $this->sendAsyncRequest($request)->wait();
        }
        // Else we want to use the synchronous call of the underlying client,
        // and not the async one in the case we have both an async and sync call
        $pluginChain = $this->createPluginChain($this->plugins, function (RequestInterface $request) {
            try {
                return new HttpFulfilledPromise($this->client->sendRequest($request));
            } catch (HttplugException $exception) {
                return new HttpRejectedPromise($exception);
            }
        });
        return $pluginChain($request)->wait();
    }
    public function sendAsyncRequest(RequestInterface $request)
    {
        $pluginChain = $this->createPluginChain($this->plugins, function (RequestInterface $request) {
            return $this->client->sendAsyncRequest($request);
        });
        return $pluginChain($request);
    }
    /**
     * Configure the plugin client.
     */
    private function configure(array $options = []): array
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(['max_restarts' => 10]);
        $resolver->setAllowedTypes('max_restarts', 'int');
        return $resolver->resolve($options);
    }
    /**
     * Create the plugin chain.
     *
     * @param Plugin[] $plugins        A plugin chain
     * @param callable $clientCallable Callable making the HTTP call
     *
     * @return callable(RequestInterface): Promise
     */
    private function createPluginChain(array $plugins, callable $clientCallable): callable
    {
        return new PluginChain($plugins, $clientCallable, $this->options);
    }
}
