<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Http\Client\Common\Plugin;

use Syde\Vendor\Zettle\Http\Client\Common\Plugin;
use Syde\Vendor\Zettle\Http\Promise\Promise;
use Syde\Vendor\Zettle\Psr\Http\Message\RequestInterface;
use Syde\Vendor\Zettle\Psr\Http\Message\UriInterface;
/**
 * Combines the AddHostPlugin and AddPathPlugin.
 *
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class BaseUriPlugin implements Plugin
{
    /**
     * @var AddHostPlugin
     */
    private $addHostPlugin;
    /**
     * @var AddPathPlugin|null
     */
    private $addPathPlugin;
    /**
     * @param UriInterface $uri        Has to contain a host name and can have a path
     * @param array        $hostConfig Config for AddHostPlugin. @see AddHostPlugin::configureOptions
     */
    public function __construct(UriInterface $uri, array $hostConfig = [])
    {
        $this->addHostPlugin = new AddHostPlugin($uri, $hostConfig);
        if (rtrim($uri->getPath(), '/')) {
            $this->addPathPlugin = new AddPathPlugin($uri);
        }
    }
    public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
    {
        $addHostNext = function (RequestInterface $request) use ($next, $first) {
            return $this->addHostPlugin->handleRequest($request, $next, $first);
        };
        if ($this->addPathPlugin) {
            return $this->addPathPlugin->handleRequest($request, $addHostNext, $first);
        }
        return $addHostNext($request);
    }
}
