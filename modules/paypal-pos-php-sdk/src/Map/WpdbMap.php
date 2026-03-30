<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Map;

use Countable;
use Exception;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DB\Table;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\IdNotFoundException;
use wpdb;
/**
 * phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
 * phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
 */
class WpdbMap implements OneToOneMapInterface, OneToManyMapInterface, MapRecordCreator, Countable, RecordMetaProvider
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
        $result = $this->wpdb->get_var($this->wpdb->prepare("\n            SELECT\n                `remote_id`\n            FROM\n                {$this->tableName()}\n            WHERE\n                `local_id` = %d\n            AND\n                `type` = %s\n            AND\n                `site_id` = %d\n        ", $localId, $this->type, $this->siteId));
        if ($result === null) {
            throw new IdNotFoundException("No remote ID found for local ID {$localId}");
        }
        return $result;
    }
    /**
     * @inheritDoc
     */
    public function remoteIds(int $localId): array
    {
        $result = $this->wpdb->get_results($this->wpdb->prepare("\n            SELECT\n                `remote_id`\n            FROM\n                {$this->tableName()}\n            WHERE\n                `local_id` = %d\n            AND\n                `type` = %s\n            AND\n                `site_id` = %d\n        ", $localId, $this->type, $this->siteId));
        if ($result === null) {
            throw new IdNotFoundException("No remote IDs found for local ID {$localId}");
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
        $result = $this->wpdb->get_var($this->wpdb->prepare("\n            SELECT\n                `local_id`\n            FROM\n                {$this->tableName()}\n            WHERE\n                `remote_id` = %s\n            AND\n                `type` = %s\n            AND\n                `site_id` = %d\n        ", $remoteId, $this->type, $this->siteId));
        if ($result === null) {
            throw new IdNotFoundException("No local ID found for remote ID {$remoteId}");
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
        $result = $this->wpdb->query($this->wpdb->prepare("\n            INSERT INTO {$this->tableName()}\n            (remote_id,local_id,type,site_id,meta)\n            VALUES (%s,%d,%s,%d,%s)\n        ", $remoteId, $localId, $this->type, $this->siteId, $meta));
        return (bool) $result;
    }
    /**
     * @inheritDoc
     */
    public function deleteRecord(int $localId, string $remoteId): bool
    {
        $result = $this->wpdb->query($this->wpdb->prepare("\n            DELETE FROM\n                {$this->tableName()}\n            WHERE\n                `remote_id` = %s\n            AND\n                `type` = %s\n            AND\n                `site_id` = %d\n        ", $remoteId, $this->type, $this->siteId));
        if ($result === null) {
            throw new IdNotFoundException(sprintf('Cannot delete record of type: %s with remote ID %s and local ID %s', $this->type, $remoteId, $localId));
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
        $result = (string) $this->wpdb->get_var($this->wpdb->prepare("\n            SELECT\n                `meta`\n            FROM\n                {$this->tableName()}\n            WHERE\n                `local_id` = %d\n            AND\n                `remote_id` = %s\n            AND\n                `type` = %s\n            AND\n                `site_id` = %d\n        ", $localId, $remoteId, $this->type, $this->siteId));
        return json_decode($result, \true);
    }
    public function count(): int
    {
        $result = $this->wpdb->get_var($this->wpdb->prepare("\n            SELECT\n                COUNT(*)\n            FROM\n                {$this->tableName()}\n            WHERE\n                `type` = %s\n            AND\n                `site_id` = %d\n        ", $this->type, $this->siteId));
        if ($result === null) {
            throw new Exception(sprintf('Count query failed: %s.', $this->wpdb->last_error));
        }
        return json_decode($result, \true);
    }
}
