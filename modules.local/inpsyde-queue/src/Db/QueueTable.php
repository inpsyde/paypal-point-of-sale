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

namespace Inpsyde\Queue\Db;

/**
 * Class QueueTable
 * @package Inpsyde\OneStock\Db
 */
class QueueTable implements Table
{
    /**
     * @var string
     */
    private $namespace;

    public function __construct(string $namespace)
    {
        $this->namespace = $namespace;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->namespace . '_queue';
    }

    /**
     * @return string
     */
    public function schema(): string
    {
        return /** @lang SQL */
            ' 
        `ID` BIGINT(20) PRIMARY KEY NOT NULL AUTO_INCREMENT,
        `hash` VARCHAR(255) NOT NULL,
        `type` VARCHAR(255) NOT NULL,
        `args` LONGTEXT NOT NULL,
        `site_id` BIGINT(20) NOT NULL,
        `created` DATETIME NOT NULL,
        `retry_count` BIGINT(20) NOT NULL,
        KEY hash (hash(' . self::MAX_INDEX_LENGTH . '))
        ';
    }
}
