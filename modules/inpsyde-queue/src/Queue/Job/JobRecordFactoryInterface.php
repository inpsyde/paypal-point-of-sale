<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job;

use Syde\Vendor\Zettle\Inpsyde\Queue\Exception\InvalidJobException;
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
