<?php

namespace Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job;

use DateTime;
use Syde\Vendor\Zettle\Inpsyde\Queue\Exception\InvalidJobException;
use stdClass;
interface JobRecordFactoryInterface
{
    /**
     * @param string $class
     * @param ContextInterface $context
     * @throws InvalidJobException
     * @return JobRecord
     */
    public function fromData(string $class, ContextInterface $context): JobRecord;
}
