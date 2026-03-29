<?php

declare(strict_types=1);

namespace Inpsyde\Queue\Queue\Job;

use Psr\Container\ContainerInterface;

class JobContainer implements ContainerInterface
{
    private ContainerInterface $parent;

    private string $prefix;

    public function __construct(ContainerInterface $parent, string $prefix)
    {
        $this->parent = $parent;
        $this->prefix = $prefix;
    }

    /**
     * @inheritDoc
     */
    public function get(string $id)
    {
        return $this->parent->get("{$this->prefix}.$id");
    }

    /**
     * @inheritDoc
     */
    public function has(string $id): bool
    {
        return $this->parent->has("{$this->prefix}.$id");
    }
}
