<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Container;

use Exception;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface;
use Syde\Vendor\Zettle\Psr\Container\NotFoundExceptionInterface;
class WpOptionContainer implements ContainerInterface, WritableContainerInterface, ClearableContainerInterface
{
    private array $options;
    private string $optionKey;
    public function __construct(string $optionKey)
    {
        $this->options = get_option($optionKey, []);
        $this->optionKey = $optionKey;
    }
    public function clear(): void
    {
        $this->options = [];
        update_option($this->optionKey, []);
    }
    public function get(string $id)
    {
        if ($this->has($id)) {
            return $this->options[$id];
        }
        $exceptionMessage = sprintf('Could not find entry %s in the "%s" wp options array', esc_html($id), esc_html($this->optionKey));
        // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        throw new class($exceptionMessage) extends Exception implements NotFoundExceptionInterface
        {
        };
    }
    public function has(string $id): bool
    {
        return isset($this->options[$id]);
    }
    public function set(string $key, mixed $value): void
    {
        $this->reload();
        $this->options[$key] = $value;
        $this->save();
    }
    public function unset(string $key): void
    {
        $this->reload();
        unset($this->options[$key]);
        $this->save();
    }
    /**
     * Writes save the whole array, so they start from the stored option: keys saved since this
     * copy was read, e.g. by the settings form in another request, would otherwise be reverted.
     * Read from the table, as get_option() returns the copy cached when this request started.
     */
    private function reload(): void
    {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- the cached copy is what must be bypassed.
        $stored = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1", $this->optionKey));
        $this->options = $stored === null ? [] : (array) maybe_unserialize($stored);
    }
    /**
     * update_option() skips the write when the value equals its cached copy, which may be as
     * stale as the one reload() bypasses; dropping it makes the comparison use the table.
     */
    private function save(): void
    {
        wp_cache_delete($this->optionKey, 'options');
        $alloptions = wp_load_alloptions();
        if (isset($alloptions[$this->optionKey])) {
            unset($alloptions[$this->optionKey]);
            wp_cache_set('alloptions', $alloptions, 'options');
        }
        update_option($this->optionKey, $this->options);
    }
}
