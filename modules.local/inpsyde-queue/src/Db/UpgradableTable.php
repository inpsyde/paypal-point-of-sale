<?php

declare(strict_types=1);

namespace Inpsyde\Queue\Db;

/**
 * A table whose schema gained columns after its first release. CREATE TABLE IF NOT EXISTS
 * leaves existing tables alone, so these are added separately when missing.
 */
interface UpgradableTable extends Table
{
    /**
     * @return array<string, string> ALTER TABLE clauses that add each column, by column name.
     */
    public function addedColumns(): array;
}
