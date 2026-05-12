<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Map;

use Countable;
use Exception;
use Syde\PayPal\PointOfSale\PhpSdk\DB\Table;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\IdNotFoundException;
use wpdb;

/**
 * phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
 * phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
 */
class WpdbMap implements
    OneToOneMapInterface,
    OneToManyMapInterface,
    MapRecordCreator,
    Countable,
    RecordMetaProvider
{
    private wpdb $wpdb;

    private Table $table;

    private string $type;

    private int $siteId;

    /**
     * WpdbMap constructor.
     *
     * @param wpdb $wpdb
     * @param Table $table
     * @param string $type
     * @param int $siteId
     */
    public function __construct(wpdb $wpdb, Table $table, string $type, int $siteId)
    {
        $this->wpdb = $wpdb;
        $this->table = $table;
        $this->type = $type;
        $this->siteId = $siteId;
    }

    /**
     * @param int $localId
     *
     * @return string
     *
     * @throws IdNotFoundException
     */
    public function remoteId(int $localId): string
    {

        $result = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "
            SELECT
                `remote_id`
            FROM
                {$this->tableName()}
            WHERE
                `local_id` = %d
            AND
                `type` = %s
            AND
                `site_id` = %d
        ",
                $localId,
                $this->type,
                $this->siteId
            )
        );

        if ($result === null) {
            throw new IdNotFoundException(sprintf("No remote ID found for local ID %d", (int) $localId));
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function remoteIds(int $localId): array
    {
        $result = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "
            SELECT
                `remote_id`
            FROM
                {$this->tableName()}
            WHERE
                `local_id` = %d
            AND
                `type` = %s
            AND
                `site_id` = %d
        ",
                $localId,
                $this->type,
                $this->siteId
            )
        );

        if (!is_array($result) || $result === []) {
            throw new IdNotFoundException(sprintf("No remote IDs found for local ID %d", (int) $localId));
        }

        return array_column($result, 'remote_id');
    }

    /**
     * @param string $remoteId
     *
     * @return int
     *
     * @throws IdNotFoundException
     */
    public function localId(string $remoteId): int
    {
        $result = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "
            SELECT
                `local_id`
            FROM
                {$this->tableName()}
            WHERE
                `remote_id` = %s
            AND
                `type` = %s
            AND
                `site_id` = %d
        ",
                $remoteId,
                $this->type,
                $this->siteId
            )
        );

        if ($result === null) {
            throw new IdNotFoundException("No local ID found for remote ID " . esc_html($remoteId));
        }

        return (int) $result;
    }

    /**
     * @return string
     */
    public function tableName(): string
    {
        return "{$this->wpdb->prefix}{$this->table->name()}";
    }

    /**
     * @inheritDoc
     */
    public function createRecord(int $localId, string $remoteId, array $arguments = []): bool
    {
        $meta = json_encode($arguments);

        $result = $this->wpdb->query(
            (string) $this->wpdb->prepare(
                "
            INSERT INTO {$this->tableName()}
            (remote_id,local_id,type,site_id,meta)
            VALUES (%s,%d,%s,%d,%s)
        ",
                $remoteId,
                $localId,
                $this->type,
                $this->siteId,
                $meta
            )
        );

        return (bool) $result;
    }

    /**
     * @inheritDoc
     */
    public function deleteRecord(int $localId, string $remoteId): bool
    {
        $result = $this->wpdb->query(
            (string) $this->wpdb->prepare(
                "
            DELETE FROM
                {$this->tableName()}
            WHERE
                `remote_id` = %s
            AND
                `type` = %s
            AND
                `site_id` = %d
        ",
                $remoteId,
                $this->type,
                $this->siteId
            )
        );

        if ($result === null) {
            throw new IdNotFoundException(
                sprintf(
                    'Cannot delete record of type: %s with remote ID %s and local ID %s',
                    esc_html($this->type),
                    esc_html($remoteId),
                    (int) $localId
                )
            );
        }

        return (bool) $result;
    }

    /**
     * @param int $localId
     * @param string $remoteId
     *
     * @return array
     */
    public function metaData(int $localId, string $remoteId): array
    {
        $result = (string) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "
            SELECT
                `meta`
            FROM
                {$this->tableName()}
            WHERE
                `local_id` = %d
            AND
                `remote_id` = %s
            AND
                `type` = %s
            AND
                `site_id` = %d
        ",
                $localId,
                $remoteId,
                $this->type,
                $this->siteId
            )
        );

        return json_decode($result, true);
    }

    public function count(): int
    {
        $result = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "
            SELECT
                COUNT(*)
            FROM
                {$this->tableName()}
            WHERE
                `type` = %s
            AND
                `site_id` = %d
        ",
                $this->type,
                $this->siteId
            )
        );

        if ($result === null) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new Exception(sprintf('Count query failed: %s.', $this->wpdb->last_error));
        }

        return json_decode($result, true);
    }
}
