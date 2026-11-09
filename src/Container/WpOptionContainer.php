<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Container;

use Exception;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

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
        $exceptionMessage = sprintf(
            'Could not find entry %s in the "%s" wp options array',
            esc_html($id),
            esc_html($this->optionKey)
        );
        // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        throw new class ($exceptionMessage) extends Exception implements NotFoundExceptionInterface {
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
        update_option($this->optionKey, $this->options);
    }

    public function unset(string $key): void
    {
        $this->reload();
        unset($this->options[$key]);
        update_option($this->optionKey, $this->options);
    }

    /**
     * Writes save the whole array, so they start from the stored option: keys saved since this
     * copy was read, e.g. by the settings form, would otherwise be reverted.
     */
    private function reload(): void
    {
        $this->options = (array) get_option($this->optionKey, []);
    }
}
