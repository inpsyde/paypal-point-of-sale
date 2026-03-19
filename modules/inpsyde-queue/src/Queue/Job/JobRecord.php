<?php

namespace Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job;

interface JobRecord
{
    public function job(): Job;
    public function context(): ContextInterface;
}
