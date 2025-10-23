<?php

/*
 * This file is part of the OneStock package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Inpsyde\Queue\Queue\Job;

use Exception;
use Inpsyde\Queue\Db\Table;
use DateTime;
use Inpsyde\Queue\Exception\InvalidJobException;
use Psr\Log\LoggerInterface;
use stdClass;
use wpdb;

/**
 * Class NetworkQueueJobRepository
 *
 * @package Inpsyde\Queue\Queue
 */
class WpDbJobRepository implements JobRepository
{

    /**
     * @var wpdb
     */
    private $database;

    /**
     * @var Table
     */
    private $queueTable;

    /**
     * @var JobRecordFactoryInterface
     */
    private $jobRecordFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * NetworkQueueJobRepository constructor.
     *
     * @param wpdb $database
     * @param Table $queueTable
     * @param JobRecordFactoryInterface $queueFactory
     */
    public function __construct(
        wpdb $database,
        Table $queueTable,
        JobRecordFactoryInterface $queueFactory,
        LoggerInterface $logger
    ) {

        $this->database = $database;
        $this->queueTable = $queueTable;
        $this->jobRecordFactory = $queueFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function add(JobRecord ...$jobRecords): bool
    {
        if (empty($jobRecords)) {
            return true;
        }

        $jobRecords = $this->filterExistingUniqueJobs(...$jobRecords);

        if (empty($jobRecords)) {
            return true;
        }

        $rowSql = '';

        array_walk(
            $jobRecords,
            function (JobRecord $jobRecord) use (&$rowSql) {
                $job = $jobRecord->job();
                $context = $jobRecord->context();

                if ($job instanceof NullJob) {
                    return;
                }

                $hash = $this->jobHash($jobRecord);

                $rowSql .= !empty($rowSql)
                    ? ', '
                    : '';

                $rowSql .= $this->database->prepare(
                    '(%s,%s,%s,%d,%s,%d)',
                    $hash,
                    $job->type(),
                    wp_json_encode($context->args()),
                    $context->forSite(),
                    $context->created()->format('Y-m-d H:i:s'),
                    $context->retryCount()
                );
            }
        );
        $sql = "
            INSERT INTO {$this->database->prefix}{$this->queueTable->name()}
            (`hash`,`type`, `args`, `site_id`, `created`, `retry_count`) VALUES
            " . $rowSql;

        $result = $this->database->query($sql);
        $this->logger->debug(
            sprintf('Added %d jobs to the queue', $result)
        );

        return (bool) $result;
    }

    /**
     * Checks the hashes of all JobRecords and filters out existing ones if they are supposed to be unique
     *
     * @param JobRecord ...$jobRecords
     *
     * @return array
     */
    private function filterExistingUniqueJobs(JobRecord ...$jobRecords): array
    {
        $uniqueEntries = array_filter(
            $jobRecords,
            static function (JobRecord $jobRecord): bool {
                return $jobRecord->job()->isUnique();
            }
        );
        $uniqueJobIds = array_map(
            function (JobRecord $jobRecord): string {
                return $this->jobHash($jobRecord);
            },
            $uniqueEntries
        );
        $existingEntries = $this->getEntriesByHash(...$uniqueJobIds);
        $existingEntriesIds = array_map(
            function (JobRecord $jobRecord): string {
                return $this->jobHash($jobRecord);
            },
            $existingEntries
        );

        return array_filter(
            $jobRecords,
            function (JobRecord $jobRecord) use ($existingEntriesIds): bool {
                return !in_array($this->jobHash($jobRecord), $existingEntriesIds, true);
            }
        );
    }

    /**
     * Returns a hash of a JobRecord object by serializing its data and type.
     *
     * @param JobRecord $jobRecord
     *
     * @return string
     */
    private function jobHash(JobRecord $jobRecord): string
    {
        $type = $jobRecord->job()->type();
        $args = json_encode($jobRecord->context()->args());

        return md5("{$type}{$args}");
    }

    /**
     * @param string ...$hashes
     *
     * @return array
     */
    private function getEntriesByHash(string ...$hashes): array
    {
        if (empty($hashes)) {
            return [];
        }
        $sanitizedHashes = array_map(
            function (string $hash): string {
                return $this->database->prepare('%s', $hash);
            },
            $hashes
        );

        $sql = "SELECT `ID` as `id`, `type`, `args`, `site_id`, `created`, `retry_count`
          FROM {$this->database->prefix}{$this->queueTable->name()}
           WHERE hash IN (" . implode(',', $sanitizedHashes) . ")";
        $result = $this->database->get_results($sql);
        $result = is_array($result)
            ? $result
            : [];

        /** @psalm-suppress PossiblyInvalidArgument */
        return array_map(
            [$this, 'castJobRecord'],
            $result
        );
    }

    /**
     * @inheritDoc
     */
    public function delete(JobRecord $jobRecord): bool
    {
        $id = $jobRecord->context()->id();
        if ($id === 0) {
            return false;
        }
        $sql = $this->database->prepare(
            "DELETE FROM {$this->database->prefix}{$this->queueTable->name()} WHERE `ID` = %d",
            $id
        );

        $result = $this->database->query($sql);
        assert(is_int($result));

        $this->logger->debug(
            sprintf('Removed %d jobs from the queue', $result)
        );
        return (bool) $result;
    }

    /**
     * @inheritDoc
     */
    public function fetch(int $limit = 1, array $types = []): array
    {
        $where = $this->whereTypes($types);
        /** @lang sql */
        $sql = "
            SELECT
                `ID` as `id`,
                `type`,
                `args`,
                `site_id`,
                `created`,
                `retry_count`
            FROM
                {$this->database->prefix}{$this->queueTable->name()}
            WHERE
                {$where}
            LIMIT 0,%d
        ";

        $result = $this->database->get_results(
            $this->database->prepare(
                $sql,
                $limit
            )
        );

        if (!$result) {
            return [];
        }

        /** @psalm-suppress PossiblyInvalidArgument */
        return array_map(
            [$this, 'castJobRecord'],
            $result
        );
    }

    /**
     * Generates the WHERE clause for an array of job types
     *
     * @param array $types
     *
     * @return string
     */
    private function whereTypes(array $types = []): string
    {
        $where = ['1=1'];
        if (!empty($types)) {
            $sanitizedTypes = array_map(
                function (string $type): string {
                    return $this->database->prepare('%s', $type);
                },
                $types
            );
            $sanitizedTypes = implode(',', $sanitizedTypes);
            $where[] = "type in ({$sanitizedTypes})";
        }

        return implode(' AND ', $where);
    }

    /**
     * @inheritDoc
     */
    public function count(array $types = []): int
    {
        $where = $this->whereTypes($types);

        $sql = "SELECT COUNT(*)
          FROM {$this->database->prefix}{$this->queueTable->name()}
          WHERE {$where}";

        $result = $this->database->get_var($sql);

        return (int) $result;
    }

    /**
     * Transforms raw DB data into a JobRecord object
     *
     * @param stdClass $row
     *
     * @return JobRecord
     * @throws InvalidJobException
     * @throws Exception
     */
    private function castJobRecord(stdClass $row): JobRecord
    {
        $context = new Context(
            json_decode($row->args),
            new DateTime($row->created),
            (int) $row->site_id,
            (int) $row->retry_count,
            (int) $row->id
        );

        return $this->jobRecordFactory->fromData(
            $row->type,
            $context
        );
    }

    /**
     * Empty the queue table
     * @return bool
     */
    public function flush(): bool
    {
        return (bool) $this->database->query(
            sprintf(
                'TRUNCATE TABLE %s%s',
                $this->database->prefix,
                $this->queueTable->name()
            )
        );
    }
}
