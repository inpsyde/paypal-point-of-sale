<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Map;

use Syde\PayPal\PointOfSale\PhpSdk\Exception\IdNotFoundException;

/**
 * Class InMemoryMap
 *
 * @package Syde\PayPal\PointOfSale\PhpSdk\Map
 */
class InMemoryMap implements
    OneToOneMapInterface,
    OneToManyMapInterface,
    MapRecordCreator,
    RecordMetaProvider
{

    /**
     * @var array
     */
    private $map;

    /**
     * @var array
     */
    private $meta = [];

    /**
     * InMemoryMap constructor.
     *
     * @param array $map
     */
    public function __construct(array $map = [])
    {
        $this->map = $map;
    }

    /**
     * @inheritDoc
     */
    public function remoteId(int $localId): string
    {
        if (!array_key_exists($localId, $this->map)) {
            throw new IdNotFoundException("No remote ID found for local ID {$localId}");
        }

        if (is_array($this->map[$localId])) {
            return current($this->map[$localId]);
        }

        return $this->map[$localId];
    }

    /**
     * @inheritDoc
     */
    public function localId(string $remoteId): int
    {
        foreach ($this->map as $localId => $entry) {
            if ($remoteId === $entry) {
                return $localId;
            }

            if (is_array($entry) && in_array($remoteId, $entry, true)) {
                return $localId;
            }
        }

        throw new IdNotFoundException("No local ID found for remote ID {$remoteId}");
    }

    /**
     * @inheritDoc
     */
    public function createRecord(int $localId, string $remoteId, array $arguments = []): bool
    {
        if (!array_key_exists($localId, $this->map)) {
            $this->map[$localId] = [];
        }

        if (in_array($remoteId, $this->map[$localId], true)) {
            return false;
        }

        $this->map[$localId][] = $remoteId;
        $this->meta[$this->metaKey($localId, $remoteId)] = $arguments;

        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteRecord(int $localId, string $remoteId): bool
    {
        if (!array_key_exists($localId, $this->map)) {
            return true;
        }

        unset($this->meta[$this->metaKey($localId, $remoteId)]);

        $entry = $this->map[$localId];

        if (!is_array($entry)) {
            unset($this->map[$localId]);

            return true;
        }

        $key = array_search($remoteId, $entry, true);

        if ($key !== false) {
            unset($entry[$key]);

            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function remoteIds(int $localId): array
    {
        if (!array_key_exists($localId, $this->map)) {
            throw new IdNotFoundException("No remote ID found for local ID {$localId}");
        }

        return (array) $this->map[$localId];
    }

    /**
     * @inheritDoc
     */
    public function metaData(int $localId, string $remoteId): array
    {
        $key = $this->metaKey($localId, $remoteId);

        if (!array_key_exists($key, $this->meta)) {
            return [];
        }

        return $this->meta[$key];
    }

    /**
     * @param int $localId
     * @param string $remoteId
     *
     * @return string
     */
    private function metaKey(int $localId, string $remoteId): string
    {
        return "{$localId}{$remoteId}";
    }
}
