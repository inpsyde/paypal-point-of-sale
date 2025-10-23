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

/**
 * Interface QueueWalker
 * @package Inpsyde\Queue\Queue
 */
interface QueueWalker
{
    /**
     * Iterates over a list of QueueItems and perform a callback on every one of them
     *
     * @param callable $callback will receive the current QueueItem as an argument
     * @return int The number of items processed
     */
    public function walk(callable $callback): int;
}
