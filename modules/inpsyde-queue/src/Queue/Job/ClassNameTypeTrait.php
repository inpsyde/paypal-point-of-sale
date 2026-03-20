<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job;

trait ClassNameTypeTrait
{
    /**
     * The type is needed to restore the specific QueueJob from the database in the QueueJobFactory.
     * @return string
     */
    public function type(): string
    {
        return get_class($this);
    }
}
