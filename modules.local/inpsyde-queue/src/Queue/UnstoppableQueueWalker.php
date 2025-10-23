<?php

declare(strict_types=1);

namespace Inpsyde\Queue\Queue;

use Iterator;

/**
 * Class TimeoutQueueWalker
 *
 * Walks the Queue until it no longer yields items
 *
 * @package Inpsyde\Queue\Queue
 */
class UnstoppableQueueWalker implements QueueWalker
{

    /**
     * @var Iterator
     */
    private $iterator;

    /**
     * UnstoppableQueueWalker constructor.
     *
     * @param Iterator $iterator
     */
    public function __construct(Iterator $iterator)
    {
        $this->iterator = $iterator;
    }

    /**
     * @inheritdoc
     */
    public function walk(callable $callback): int
    {
        $count = 0;
        foreach ($this->iterator as $current) {
            $callback($current);
        }
        $this->iterator->rewind();

        return $count;
    }
}
