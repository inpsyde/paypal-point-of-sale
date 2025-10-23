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

namespace Inpsyde\Queue\Queue\Job;

use DateTime;
use Psr\Log\LoggerInterface;
use stdClass;

/**
 * Class NullJob
 *
 * @package Inpsyde\Queue\Queue
 */
class NullJob implements Job
{

    /**
     * @return bool
     */
    public function execute(
        ContextInterface $context,
        JobRepository $repository,
        LoggerInterface $logger
    ): bool {
        return true;
    }

    /**
     * @return bool
     */
    public function isUnique(): bool
    {
        return false;
    }

    /**
     * @return stdClass
     */
    public function args(): stdClass
    {
        return new stdClass();
    }

    /**
     * @return string
     */
    public function type(): string
    {
        return '';
    }
}
