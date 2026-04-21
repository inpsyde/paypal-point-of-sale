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
 * Class ItemsCountStopper
 * @package Inpsyde\Queue\Queue
 */
class ItemsCountStopper implements Stopper
{
    private int $processedItems = 0;

    private int $maxItems = 0;

    /**
     * ItemsCountStopper constructor.
     * @param int $maxItems
     */
    public function __construct(int $maxItems)
    {
        $this->maxItems = $maxItems;
    }

    /**
     * @return bool
     */
    public function start(): bool
    {
        $this->processedItems = 0;
        return $this->processedItems === 0;
    }

    /**
     * @return bool
     */
    public function isStopped(): bool
    {
        if ($this->maxItems === -1) {
            return false;
        }

        $this->processedItems++;

        return $this->maxItems <= $this->processedItems;
    }
}
