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
 * Interface Table
 */
interface Table
{
    const MAX_INDEX_LENGTH = 191;

    /**
     * The name of the database table
     *
     * @return string
     */
    public function name(): string;

    /**
     * The table schema
     *
     * @return string
     */
    public function schema(): string;
}
