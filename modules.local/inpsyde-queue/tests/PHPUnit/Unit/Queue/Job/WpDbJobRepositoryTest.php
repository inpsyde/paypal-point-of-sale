<?php
declare(strict_types=1);

namespace Inpsyde\OneStock\UnitTests\Queue;

use DateTime;
use DateTimeZone;
use Inpsyde\Queue\Db\Table;
use Inpsyde\Queue\Exception\InvalidJobException;
use Inpsyde\Queue\Queue\Job\Context;
use Inpsyde\Queue\Queue\Job\ContextInterface;
use Inpsyde\Queue\Queue\Job\Job;
use Inpsyde\Queue\Queue\Job\JobRecord;
use Inpsyde\Queue\Queue\Job\JobRecordFactoryInterface;
use Inpsyde\Queue\Queue\Job\WpDbJobRepository;
use Mockery;
use MonkeryTestCase\BrainMonkeyWpTestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use stdClass;
use wpdb;

use function Brain\Monkey\Functions\when;

class WpDbJobRepositoryTest extends BrainMonkeyWpTestCase
{

    /**
     * Tests if the WpDbJobRepository produces the expected SQL when requested to return jobs
     * of specific types
     */
    public function testFetchOfType()
    {
        $types = ['foo', 'bar', 'baz'];
        $queryResult = [];
        foreach ($types as $type) {
            $queryResult[] = $this->mockRow(['type' => $type]);
        }

        $wpdb = Mockery::mock(wpdb::class);
        $wpdb->prefix = 'wp_';
        $wpdb->shouldReceive('prepare')->times(count($types))->andReturnUsing(
            function ($template, $value) use ($types): string {
                $args = func_get_args();
                $this->assertContains($value, $types);

                return $value;
            }
        );
        /**
         * Add expectation about the produced SQL
         * This of course needs to be pretty fuzzy
         */
        $wpdb->shouldReceive('prepare')->once()->andReturnUsing(
            function (string $sql, $value) use ($types): string {
                $typesPattern = implode(',.*', $types);
                $regex = '/.*WHERE.+type.+in.*\(.*'.$typesPattern.'.*\).*/s';
                $this->assertTrue(
                    (bool) preg_match($regex, $sql),
                    "Expecting specified types to be used in SQL"
                );

                return $sql;
            }
        );
        $wpdb->shouldReceive('get_results')->once()->andReturnUsing(
            function () use ($queryResult): array {
                return $queryResult;
            }
        );
        $table = Mockery::mock(Table::class);
        $table->shouldReceive('name')->andReturn('table');
        $recordFactory = Mockery::mock(JobRecordFactoryInterface::class);
        $logger = new NullLogger();
        foreach ($queryResult as $row) {
            $recordFactory->shouldReceive('fromData')->once();
        }
        $testee = new WpDbJobRepository($wpdb, $table, $recordFactory, $logger);
        $jobRecords = $testee->fetch(100, $types);
        $this->assertContainsOnlyInstancesOf(JobRecord::class, $jobRecords);
    }

    public function testFetchLeavesOutJobsThatAreNotDueYet(): void
    {
        $wpdb = Mockery::mock(wpdb::class);
        $wpdb->prefix = 'wp_';
        $wpdb->shouldReceive('prepare')->once()->andReturnUsing(
            function (string $sql, string $now, int $limit): string {
                $this->assertMatchesRegularExpression('/`not_before` IS NULL OR `not_before` <= %s/', $sql);
                $this->assertEqualsWithDelta(time(), strtotime($now . ' UTC'), 2);
                $this->assertSame(10, $limit);

                return $sql;
            }
        );
        $wpdb->shouldReceive('get_results')->once()->andReturn([]);
        $table = Mockery::mock(Table::class);
        $table->shouldReceive('name')->andReturn('table');

        $testee = new WpDbJobRepository($wpdb, $table, Mockery::mock(JobRecordFactoryInterface::class), new NullLogger());

        $this->assertSame([], $testee->fetch(10));
    }

    public function testReadsNotBeforeAsUtc(): void
    {
        $wpdb = Mockery::mock(wpdb::class);
        $wpdb->prefix = 'wp_';
        $wpdb->shouldReceive('prepare')->andReturnUsing(static fn (string $sql): string => $sql);
        $wpdb->shouldReceive('get_results')->once()->andReturn([
            $this->mockRow(['not_before' => '2026-11-08 10:00:00']),
            $this->mockRow(['not_before' => null]),
        ]);
        $table = Mockery::mock(Table::class);
        $table->shouldReceive('name')->andReturn('table');
        $notBefore = [];
        $recordFactory = Mockery::mock(JobRecordFactoryInterface::class);
        $recordFactory->shouldReceive('fromData')->twice()->andReturnUsing(
            static function (string $type, ContextInterface $context) use (&$notBefore): JobRecord {
                $notBefore[] = $context->notBefore() ? $context->notBefore()->getTimestamp() : null;

                return Mockery::mock(JobRecord::class);
            }
        );

        (new WpDbJobRepository($wpdb, $table, $recordFactory, new NullLogger()))->fetch(10);

        $this->assertSame([strtotime('2026-11-08 10:00:00 UTC'), null], $notBefore);
    }

    public function testFetchRemovesJobsOfUnknownTypesAndReturnsTheOthers(): void
    {
        $wpdb = Mockery::mock(wpdb::class);
        $wpdb->prefix = 'wp_';
        $wpdb->shouldReceive('prepare')->andReturnUsing(
            static fn (string $template, ...$args): string => vsprintf(str_replace('%d', '%s', $template), $args)
        );
        $wpdb->shouldReceive('get_results')->once()->andReturn([
            $this->mockRow(['id' => 1, 'type' => 'create-order']),
            $this->mockRow(['id' => 2, 'type' => 'sync-product']),
        ]);
        $deleted = [];
        $wpdb->shouldReceive('query')->andReturnUsing(static function (string $sql) use (&$deleted): int {
            $deleted[] = $sql;

            return 1;
        });
        $table = Mockery::mock(Table::class);
        $table->shouldReceive('name')->andReturn('table');
        $record = Mockery::mock(JobRecord::class);
        $recordFactory = Mockery::mock(JobRecordFactoryInterface::class);
        $recordFactory->shouldReceive('fromData')->andReturnUsing(
            static function (string $type) use ($record): JobRecord {
                if ($type === 'create-order') {
                    throw new InvalidJobException("Job type '{$type}' could not be found");
                }

                return $record;
            }
        );

        $records = (new WpDbJobRepository($wpdb, $table, $recordFactory, new NullLogger()))->fetch(10);

        $this->assertSame([$record], $records);
        $this->assertCount(1, $deleted);
        $this->assertStringEndsWith('WHERE `ID` = 1', $deleted[0]);
    }

    public function testStoresNotBeforeInUtcOrNull(): void
    {
        when('wp_json_encode')->alias('json_encode');
        $wpdb = Mockery::mock(wpdb::class);
        $wpdb->prefix = 'wp_';
        $wpdb->shouldReceive('prepare')->andReturnUsing(
            static fn (string $template, ...$args): string => vsprintf(str_replace(['%d', '%s'], ['%s', "'%s'"], $template), $args)
        );
        $sql = '';
        $wpdb->shouldReceive('query')->once()->andReturnUsing(static function (string $query) use (&$sql): int {
            $sql = $query;

            return 2;
        });
        $table = Mockery::mock(Table::class);
        $table->shouldReceive('name')->andReturn('table');
        $later = new DateTime('2026-11-08 12:00:00', new DateTimeZone('Europe/Berlin'));

        (new WpDbJobRepository($wpdb, $table, Mockery::mock(JobRecordFactoryInterface::class), new NullLogger()))->add(
            $this->record(Context::fromArray([], 1, 1, null, 0, $later)),
            $this->record(Context::fromArray(['other' => true], 1))
        );

        $this->assertStringContainsString('`not_before`) VALUES', $sql);
        $this->assertStringContainsString(",'2026-11-08 11:00:00')", $sql);
        $this->assertStringContainsString(',NULL)', $sql);
    }

    private function record(ContextInterface $context): JobRecord
    {
        $job = Mockery::mock(Job::class);
        $job->shouldReceive('type')->andReturn('type');
        $job->shouldReceive('isUnique')->andReturn(false);
        $record = Mockery::mock(JobRecord::class);
        $record->shouldReceive('job')->andReturn($job);
        $record->shouldReceive('context')->andReturn($context);

        return $record;
    }

    public function mockRow(array $data = []): stdClass
    {
        $row = array_merge(
            [
                'args' => json_encode(['foo' => 'bar']),
                'created' => (new DateTime())->format('Y-m-d H:i:s'),
                'site_id' => 1,
                'retry_count' => 1,
                'type' => 'HJLKVHJGFZJCHFJG',
                'id' => rand(0, PHP_INT_MAX),
            ],
            $data
        );

        return (object) $row;
    }
}
