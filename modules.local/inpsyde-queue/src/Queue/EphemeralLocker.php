<?php

declare(strict_types=1);

namespace Inpsyde\Queue\Queue;

/**
 * Class EphemeralLocker
 * Locks and unlocks without providing any sort of persistence.
 * Used for acceptance testing
 *
 * @package Inpsyde\Queue\Queue
 */
class EphemeralLocker implements Locker
{

    private $isLocked = false;

    /**
     * @inheritDoc
     */
    public function lock(): bool
    {
        $this->isLocked = true;

        return true;
    }

    /**
     * @inheritDoc
     */
    public function unlock(): bool
    {
        $this->isLocked = false;

        return true;
    }

    /**
     * @inheritDoc
     */
    public function isLocked(): bool
    {
        return $this->isLocked;
    }
}
