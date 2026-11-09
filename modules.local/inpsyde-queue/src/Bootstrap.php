<?php

declare(strict_types=1);

namespace Inpsyde\Queue;

use Inpsyde\Queue\Db\Table;
use Inpsyde\Queue\Db\UpgradableTable;

class Bootstrap
{
    public const RETRY_LOCK_SUFFIX = '_retry';

    /**
     * @var Table[]
     */
    private array $tables;

    public function __construct(Table ...$tables)
    {
        $this->tables = $tables;
    }

    public function activate(): void
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->get_blog_prefix();
        foreach ($this->tables as $table) {
            //phpcs:disable Syde.Files.LineLength.TooLong
            $sql = "CREATE TABLE IF NOT EXISTS {$prefix}{$table->name()} ({$table->schema()}) $charset_collate;";
            // phpcs:ignore WordPress.DB.PreparedSQL
            $wpdb->query($sql);
            if ($table instanceof UpgradableTable) {
                $this->addMissingColumns("{$prefix}{$table->name()}", $table);
            }
        }
    }

    /**
     * Records the schema in $schemaOption only once all columns exist. A failed upgrade, e.g.
     * without ALTER rights, is retried at most hourly.
     */
    public function upgrade(string $schemaOption): void
    {
        global $wpdb;

        $schema = md5((string) wp_json_encode(array_map(
            static fn (Table $table): array => [$table->name(), $table->schema()],
            $this->tables
        )));
        $retryLock = $schemaOption . self::RETRY_LOCK_SUFFIX;
        if (get_option($schemaOption) === $schema || get_transient($retryLock)) {
            return;
        }
        // Also keeps concurrent requests from running the same ALTER.
        set_transient($retryLock, 1, HOUR_IN_SECONDS);

        // A failing statement mustn't print a database error into the response.
        $suppressed = $wpdb->suppress_errors();
        try {
            $this->activate();
            $upToDate = $this->isUpToDate();
        } finally {
            $wpdb->suppress_errors($suppressed);
        }
        if ($upToDate) {
            update_option($schemaOption, $schema, true);
            delete_transient($retryLock);
        }
    }

    /**
     * Whether all columns added since the tables' first release exist.
     */
    public function isUpToDate(): bool
    {
        global $wpdb;

        $prefix = $wpdb->get_blog_prefix();
        foreach ($this->tables as $table) {
            if (!$table instanceof UpgradableTable) {
                continue;
            }
            foreach (array_keys($table->addedColumns()) as $column) {
                if (!$this->hasColumn("{$prefix}{$table->name()}", $column)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
     */
    private function addMissingColumns(string $tableName, UpgradableTable $table): void
    {
        global $wpdb;

        foreach ($table->addedColumns() as $column => $clause) {
            if (!$this->hasColumn($tableName, $column)) {
                $wpdb->query("ALTER TABLE {$tableName} {$clause}");
            }
        }
    }

    /**
     * phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
     */
    private function hasColumn(string $tableName, string $column): bool
    {
        global $wpdb;

        return $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$tableName} LIKE %s", $column)) !== null;
    }

    /**
     * phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
     */
    public function deactivate(): void
    {
        global $wpdb;

        $prefix = $wpdb->get_blog_prefix();

        foreach ($this->tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$prefix}{$table->name()}");
        }
    }
}
