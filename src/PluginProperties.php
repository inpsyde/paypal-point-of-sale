<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale;

use Error;

/**
 * @method string name()
 * @method string pluginUri()
 * @method string version()
 * @method string description()
 * @method string author()
 * @method string authorUri()
 * @method string textDomain()
 * @method string domainPath()
 * @method string network()
 * @method string requiresWp()
 * @method string requiresPhp()
 */
class PluginProperties
{
    private const HEADER_PROPERTIES = [
        'name' => 'Name',
        'pluginUri' => 'PluginURI',
        'version' => 'Version',
        'description' => 'Description',
        'author' => 'Author',
        'authorUri' => 'AuthorURI',
        'textDomain' => 'TextDomain',
        'domainPath' => 'DomainPath',
        'network' => 'Network',
        'requiresWp' => 'RequiresWP',
        'requiresPhp' => 'RequiresPHP',
    ];

    private string $basePath;

    private string $baseUrl;

    private string $basename;

    private array $data;

    private bool $debug;

    private ?int $lastUpdateTimestamp = null;

    /**
     * @param string $pluginFile
     */
    public function __construct(string $pluginFile)
    {
        $this->basePath = plugin_dir_path($pluginFile);
        $this->baseUrl = plugins_url('/', $pluginFile);
        $this->basename = plugin_basename($pluginFile);

        if (!function_exists('get_plugin_data')) {
            /** @psalm-suppress MissingFile */
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $this->data = get_plugin_data($pluginFile, false, false);
        $this->debug = defined('WP_DEBUG') && WP_DEBUG;

        $this->lastUpdateTimestamp = filemtime($pluginFile) ?: null;
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return string
     */
    public function __call(string $name, array $arguments): string
    {
        $key = self::HEADER_PROPERTIES[$name] ?? null;

        if (!$key) {
            throw new Error(sprintf('Call to undefined method %s::%s().', __CLASS__, $name));
        }

        return (string) ($this->data[$key] ?? '');
    }

    /**
     * @return string
     */
    public function basePath(): string
    {
        return $this->basePath;
    }

    /**
     * @return string
     */
    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * @return string
     */
    public function basename(): string
    {
        return $this->basename;
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * @return void
     */
    public function forceDebug(): void
    {
        $this->debug = true;
    }

    /**
     * @return void
     */
    public function forceNoDebug(): void
    {
        $this->debug = false;
    }

    public function lastUpdateTimestamp(): ?int
    {
        return $this->lastUpdateTimestamp;
    }
}
