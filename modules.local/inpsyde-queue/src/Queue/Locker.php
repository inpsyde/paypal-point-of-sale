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
 * Interface Locker
 * @package Inpsyde\Queue\Queue
 */
interface Locker
{
    /**
     * @return bool
     */
    public function lock(): bool;

    /**
     * @return bool
     */
    public function unlock(): bool;

    /**
     * @return bool
     */
    public function isLocked(): bool;
}
