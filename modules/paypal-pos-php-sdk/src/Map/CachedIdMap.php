<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Map;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\IdNotFoundException;
/**
 * Class CachedIdMap
 * Decorates another ID Map and caches its results
 *
 * @package Syde\PayPal\PointOfSale\PhpSdk\Map
 */
class CachedIdMap implements OneToOneMapInterface, OneToManyMapInterface, MapRecordCreator
{
    private $localIdCache = [];
    private $remoteIdCache = [];
    private OneToOneMapInterface $base;
    public function __construct(LocalIdProvider $base)
    {
        $this->base = $base;
    }
    /**
     * @inheritDoc
     */
    public function remoteId(int $localId): string
    {
        $this->assertBaseInstance(RemoteIdProvider::class);
        $key = (string) $localId;
        if (!array_key_exists($key, $this->remoteIdCache)) {
            $result = $this->base->remoteId($localId);
            $this->remoteIdCache[$key] = $result;
        }
        return $this->remoteIdCache[$key];
    }
    /**
     * @param string $className
     *
     * @throws IdNotFoundException
     */
    private function assertBaseInstance(string $className)
    {
        if (!$this->base instanceof $className) {
            throw new IdNotFoundException("Base map does not implement {$className}");
        }
    }
    /**
     * @inheritDoc
     */
    public function localId(string $remoteId): int
    {
        if (!array_key_exists($remoteId, $this->localIdCache)) {
            $result = (int) $this->base->localId($remoteId);
            $this->localIdCache[$remoteId] = $result;
        }
        return $this->localIdCache[$remoteId];
    }
    public function createRecord(int $localId, string $remoteId, array $arguments = []): bool
    {
        /**
         * Clear any cache we might have first
         */
        $this->deleteRecord($localId, $remoteId);
        if ($this->base instanceof MapRecordCreator) {
            return $this->base->createRecord($localId, $remoteId);
        }
        return \false;
    }
    public function deleteRecord(int $localId, string $remoteId): bool
    {
        unset($this->localIdCache[(string) $localId]);
        unset($this->remoteIdCache[$remoteId]);
        if ($this->base instanceof MapRecordCreator) {
            return $this->base->deleteRecord($localId, $remoteId);
        }
        return \true;
    }
    /**
     * @inheritDoc
     */
    public function remoteIds(int $localId): array
    {
        $this->assertBaseInstance(MultipleRemoteIdProvider::class);
        $key = (string) $localId;
        if (!array_key_exists($key, $this->remoteIdCache)) {
            $result = (array) $this->base->remoteIds($localId);
            $this->remoteIdCache[$key] = $result;
        }
        return $this->remoteIdCache[$key];
    }
}
