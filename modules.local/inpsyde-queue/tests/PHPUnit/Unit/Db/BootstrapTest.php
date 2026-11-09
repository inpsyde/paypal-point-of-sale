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

    /**
     * @var array<string, mixed>
     */
    private array $transients = [];

    /**
     * @param bool ...$columnFound What each SHOW COLUMNS lookup finds, in order.
     */
    private function stubWpdb(bool ...$columnFound): void
    {
        global $wpdb;
        $wpdb = Mockery::mock(wpdb::class);
        $wpdb->shouldReceive('get_charset_collate')->andReturn('');
        $wpdb->shouldReceive('suppress_errors')->andReturn(false);
        $wpdb->shouldReceive('get_blog_prefix')->andReturn('wp_');
        $wpdb->shouldReceive('prepare')->andReturnUsing(static fn (string $sql): string => $sql);
        $wpdb->shouldReceive('query')->andReturnUsing(function (string $sql): bool {
            $this->queries[] = $sql;

            return true;
        });
        $wpdb->shouldReceive('get_var')->andReturnValues(
            array_map(static fn (bool $found): ?string => $found ? 'not_before' : null, $columnFound)
        );
    }

    protected function setUp(): void
    {
        parent::setUp();
        if (!defined('HOUR_IN_SECONDS')) {
            define('HOUR_IN_SECONDS', 3600);
        }
        when('wp_json_encode')->alias('json_encode');
        when('get_transient')->alias(fn (string $name) => $this->transients[$name] ?? false);
        when('set_transient')->alias(function (string $name, $value): bool {
            $this->transients[$name] = $value;

            return true;
        });
        when('delete_transient')->alias(function (string $name): bool {
            unset($this->transients[$name]);

            return true;
        });
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
        $this->stubWpdb(true, true);
        $bootstrap->upgrade('zettle_queue_schema');
        $this->queries = [];

        when('get_option')->justReturn($stored);
        $this->stubWpdb();
        $bootstrap->upgrade('zettle_queue_schema');

        self::assertSame([], $this->queries);
    }

    public function testAddsTheMissingColumnAndRecordsTheSchema(): void
    {
        when('get_option')->justReturn(false);
        expect('update_option')->once()->with('zettle_queue_schema', Mockery::type('string'), true);
        $this->stubWpdb(false, true);

        (new Bootstrap(new QueueTable('zettle')))->upgrade('zettle_queue_schema');

        self::assertStringContainsString('ALTER TABLE wp_zettle_queue ADD COLUMN `not_before`', implode("\n", $this->queries));
    }

    public function testRetriesAnHourLaterWhenTheColumnCouldNotBeAdded(): void
    {
        when('get_option')->justReturn(false);
        expect('update_option')->never();
        $this->stubWpdb(false, false);
        $bootstrap = new Bootstrap(new QueueTable('zettle'));

        $bootstrap->upgrade('zettle_queue_schema');
        $bootstrap->upgrade('zettle_queue_schema');

        self::assertCount(1, preg_grep('/^ALTER TABLE/', $this->queries));
        self::assertArrayHasKey('zettle_queue_schema_retry', $this->transients);
    }
}
