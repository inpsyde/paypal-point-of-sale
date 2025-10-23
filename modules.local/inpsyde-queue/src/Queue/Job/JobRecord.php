<?php

namespace Inpsyde\Queue\Queue\Job;

interface JobRecord
{

    public function job(): Job;

    public function context(): ContextInterface;
}
