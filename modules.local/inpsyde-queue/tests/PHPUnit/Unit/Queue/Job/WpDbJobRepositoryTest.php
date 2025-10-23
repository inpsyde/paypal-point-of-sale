<?php
declare(strict_types=1);

namespace Inpsyde\OneStock\UnitTests\Queue;

use DateTime;
use Inpsyde\Queue\Db\Table;
use Inpsyde\Queue\Queue\Job\JobRecord;
use Inpsyde\Queue\Queue\Job\JobRecordFactoryInterface;
use Inpsyde\Queue\Queue\Job\WpDbJobRepository;
use Mockery;
use MonkeryTestCase\BrainMonkeyWpTestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use stdClass;
use wpdb;

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
