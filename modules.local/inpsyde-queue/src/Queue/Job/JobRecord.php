<?php

declare(strict_types=1);

namespace Inpsyde\Queue\Queue\Job;

interface JobRecord
{
    public function job(): Job;

    public function context(): ContextInterface;
}
