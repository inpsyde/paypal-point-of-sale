<?php

declare(strict_types=1);

namespace Inpsyde\OneStock\UnitTests\Db;

use Inpsyde\Queue\Bootstrap;
use Inpsyde\Queue\Db\QueueTable;
use Mockery;
use MonkeryTestCase\BrainMonkeyWpTestCase;
use wpdb;

use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\when;

class BootstrapTest extends BrainMonkeyWpTestCase
{
    /**
     * @var string[]
     */
    private array $queries = [];

    private function wpdb(bool ...$columnFound): wpdb
    {
        global $wpdb;
        $wpdb = Mockery::mock(wpdb::class);
        $wpdb->shouldReceive('get_charset_collate')->andReturn('');
        $wpdb->shouldReceive('get_blog_prefix')->andReturn('wp_');
        $wpdb->shouldReceive('prepare')->andReturnUsing(static fn (string $sql): string => $sql);
        $wpdb->shouldReceive('query')->andReturnUsing(function (string $sql): bool {
            $this->queries[] = $sql;

            return true;
        });
        $wpdb->shouldReceive('get_var')->andReturnValues(
            array_map(static fn (bool $found): ?string => $found ? 'not_before' : null, $columnFound)
        );

        return $wpdb;
    }

    protected function setUp(): void
    {
        parent::setUp();
        when('wp_json_encode')->alias('json_encode');
    }

    public function testDoesNothingWhileTheSchemaIsCurrent(): void
    {
        $bootstrap = new Bootstrap(new QueueTable('zettle'));
        $stored = null;
        when('get_option')->justReturn(false);
        when('update_option')->alias(static function (string $name, string $schema) use (&$stored): bool {
            $stored = $schema;

            return true;
        });
        $this->wpdb(true, true);
        $bootstrap->upgrade('zettle_queue_schema');
        $this->queries = [];

        when('get_option')->justReturn($stored);
        $this->wpdb();
        $bootstrap->upgrade('zettle_queue_schema');

        self::assertSame([], $this->queries);
    }

    public function testAddsTheMissingColumnAndRecordsTheSchema(): void
    {
        when('get_option')->justReturn(false);
        expect('update_option')->once()->with('zettle_queue_schema', Mockery::type('string'), true);
        $this->wpdb(false, true);

        (new Bootstrap(new QueueTable('zettle')))->upgrade('zettle_queue_schema');

        self::assertStringContainsString('ALTER TABLE wp_zettle_queue ADD COLUMN `not_before`', $this->queries[1]);
    }

    public function testRetriesLaterWhenTheColumnCouldNotBeAdded(): void
    {
        when('get_option')->justReturn(false);
        expect('update_option')->never();
        $this->wpdb(false, false);

        (new Bootstrap(new QueueTable('zettle')))->upgrade('zettle_queue_schema');

        self::assertCount(2, $this->queries);
    }
}
