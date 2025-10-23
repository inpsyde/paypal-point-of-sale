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
 * Class Timer
 *
 * We want the queue to max. execute a predefined value of microseconds. The timer
 * basically gives us a timer to do so. True, the implementation is not exact science.
 *
 * @package Inpsyde\Queue\Queue
 */
class TimeStopper implements Stopper
{
    /**
     * @var int
     */
    private $startedAt;

    /**
     * @var int
     */
    private $stopAfter;

    /**
     * Timer constructor.
     * @param float $seconds
     */
    public function __construct(float $seconds)
    {
        $this->stopAfter = $seconds * 1000;
    }

    /**
     * @return bool
     */
    public function start(): bool
    {
        $this->startedAt = $this->timestamp();
        return $this->startedAt !== null;
    }

    /**
     * @return bool
     */
    public function isStopped(): bool
    {
        return ($this->timestamp() - $this->startedAt) > $this->stopAfter;
    }

    /**
     * @return float
     */
    private function timestamp(): float
    {
        return round(microtime(true) * 1000);
    }
}
