<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DB;

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
