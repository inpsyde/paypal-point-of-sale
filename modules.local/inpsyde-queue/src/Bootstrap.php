<?php

declare(strict_types=1);

namespace Inpsyde\Queue;

use Inpsyde\Queue\Db\Table;

class Bootstrap
{

    /**
     * @var Table[]
     */
    private $tables;

    public function __construct(Table ...$tables)
    {
        $this->tables = $tables;
    }

    public function activate()
    {
        global $wpdb;
        //phpcs:disable Inpsyde.CodeQuality.VariablesName.SnakeCaseVar
        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->get_blog_prefix();
        foreach ($this->tables as $table) {
            //phpcs:disable Inpsyde.CodeQuality.LineLength.TooLong
            $sql = "CREATE TABLE IF NOT EXISTS {$prefix}{$table->name()} ({$table->schema()}) $charset_collate;";
            // phpcs:ignore WordPress.DB.PreparedSQL
            $wpdb->query($sql);
        }
    }

    /**
     * phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
     */
    public function deactivate()
    {
        global $wpdb;

        $prefix = $wpdb->get_blog_prefix();

        foreach ($this->tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$prefix}{$table->name()}");
        }
    }
}
