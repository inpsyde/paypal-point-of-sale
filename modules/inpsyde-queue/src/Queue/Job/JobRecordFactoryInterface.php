<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job;

use Syde\Vendor\Zettle\Inpsyde\Queue\Exception\InvalidJobException;
interface JobRecordFactoryInterface
{
    /**
     * @param string $type
     * @param ContextInterface $context
     * @throws InvalidJobException
     * @return JobRecord
     */
    public function fromData(string $type, ContextInterface $context): JobRecord;
}
