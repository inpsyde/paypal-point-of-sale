<?php

declare(strict_types=1);

namespace Inpsyde\Queue;

use Inpsyde\Queue\Db\Table;
use Inpsyde\Queue\Db\UpgradableTable;

class Bootstrap
{
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
     * Brings the tables up to date once per schema change. $schemaOption only records the schema
     * once all columns exist, so a failed upgrade, e.g. without ALTER rights, is retried on the
     * next request instead of leaving the queue writing to columns that don't exist.
     */
    public function upgrade(string $schemaOption): void
    {
        $schema = md5((string) wp_json_encode(array_map(
            static fn (Table $table): array => [$table->name(), $table->schema()],
            $this->tables
        )));
        if (get_option($schemaOption) === $schema) {
            return;
        }

        $this->activate();
        if ($this->hasAddedColumns()) {
            update_option($schemaOption, $schema, true);
        }
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

    private function hasAddedColumns(): bool
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
