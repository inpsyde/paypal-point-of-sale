<?php

/*
 * This file is part of the OneStock package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Inpsyde\Queue\Queue;

use Iterator;

/**
 * Class TimeoutQueueWalker
 *
 * Walks the Queue until a specified timeout is hit
 *
 * @package Inpsyde\Queue\Queue
 */
class StoppableQueueWalker implements QueueWalker
{

    /**
     * @var Iterator
     */
    private $iterator;

    /**
     * @var Stopper
     */
    private $stopper;

    /**
     * TimeoutQueueWalker constructor.
     *
     * @param Iterator $iterator
     * @param Stopper $stopper
     */
    public function __construct(Iterator $iterator, Stopper $stopper)
    {
        $this->iterator = $iterator;
        $this->stopper = $stopper;
    }

    /**
     * @inheritdoc
     */
    public function walk(callable $callback): int
    {
        $this->stopper->start();

        $count = 0;

        foreach ($this->iterator as $current) {
            $callback($current);

            $count++;

            if ($this->stopper->isStopped()) {
                break;
            }
        }

        $this->iterator->rewind();

        return $count;
    }
}
