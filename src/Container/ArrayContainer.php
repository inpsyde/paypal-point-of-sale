<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Container;

use Exception;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class ArrayContainer implements ContainerInterface, WritableContainerInterface, ClearableContainerInterface
{
    /**
     * @var array
     */
    private $options;

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    /**
     * @inheritDoc
     */
    public function get($id)
    {
        if (!$this->has($id)) {
            $exceptionMessage = sprintf(
                'Could not find entry %s in the options array',
                $id
            );
            throw new class ($exceptionMessage) extends Exception implements NotFoundExceptionInterface {

            };
        }
        return $this->options[$id];
    }

    /**
     * @inheritDoc
     */
    public function has($id)
    {
        return array_key_exists($id, $this->options);
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $value): void
    {
        $this->options[$key] = $value;
    }

    public function unset(string $key): void
    {
        if ($this->has($key)) {
            unset($this->options[$key]);
        }
    }

    public function clear(): void
    {
        $this->options = [];
    }
}
