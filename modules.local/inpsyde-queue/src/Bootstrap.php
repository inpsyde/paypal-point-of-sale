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
     * phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
     */
    private function addMissingColumns(string $tableName, UpgradableTable $table): void
    {
        global $wpdb;

        foreach ($table->addedColumns() as $column => $clause) {
            $exists = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$tableName} LIKE %s", $column));
            if ($exists === null) {
                $wpdb->query("ALTER TABLE {$tableName} {$clause}");
            }
        }
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
