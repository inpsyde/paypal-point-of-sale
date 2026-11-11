<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Adapter\Unleash\Cache;

use DateInterval;
use Syde\Vendor\Zettle\Psr\SimpleCache\CacheInterface;
/**
 * The SDK's metrics cache. The SDK reads and writes the metrics bucket on every flag check,
 * so this keeps changes in memory and writes them to the underlying cache on flush(), at most
 * once per WRITE_INTERVAL. Requests in between drop their counts, so on busy sites the counts
 * sent to Unleash are a sample. Deletions, i.e. after the SDK has sent the metrics, are written
 * immediately, so other requests stop loading the sent bucket, and the next value after a
 * deletion is always written, so the same counts aren't sent twice.
 *
 * @internal Built by UnleashProjectBuilder, which flushes it on shutdown.
 */
final class MetricsBuffer implements CacheInterface
{
    public const WRITE_INTERVAL = 300;
    /**
     * Values read or written in this request. null stands for a deleted value.
     *
     * @var array<string, mixed>
     */
    private array $values = [];
    /**
     * TTLs of the values written in this request, by key.
     *
     * @var array<string, null|int|DateInterval>
     */
    private array $changed = [];
    /**
     * When the stored values were written, by key.
     *
     * @var array<string, int|null>
     */
    private array $writtenAt = [];
    /**
     * @param \Closure(): int $clock
     */
    public function __construct(private readonly CacheInterface $cache, private readonly \Closure $clock)
    {
    }
    /**
     * Writes the changed values to the underlying cache, unless they were written less than
     * WRITE_INTERVAL seconds ago.
     */
    public function flush(): void
    {
        $now = ($this->clock)();
        foreach ($this->changed as $key => $ttl) {
            $value = $this->values[$key] ?? null;
            if ($value === null) {
                $this->cache->delete($key);
                continue;
            }
            $writtenAt = $this->writtenAt[$key] ?? null;
            if ($writtenAt !== null && $now - $writtenAt < self::WRITE_INTERVAL) {
                continue;
            }
            $this->cache->set($key, ['value' => $value, 'written_at' => $now], $ttl);
            $this->writtenAt[$key] = $now;
        }
        $this->changed = [];
    }
    public function get(string $key, mixed $default = null): mixed
    {
        if (!array_key_exists($key, $this->values)) {
            $this->load($key);
        }
        return $this->values[$key] ?? $default;
    }
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->values[$key] = $value;
        $this->changed[$key] = $ttl;
        return \true;
    }
    public function delete(string $key): bool
    {
        // The SDK deletes the bucket after sending it and then starts a new one. The deletion
        // is written now and the new bucket on flush(), whatever the interval, or the stored,
        // already sent bucket would be sent again.
        $this->values[$key] = null;
        $this->writtenAt[$key] = null;
        unset($this->changed[$key]);
        return $this->cache->delete($key);
    }
    public function clear(): bool
    {
        foreach (array_keys($this->values) as $key) {
            $this->delete($key);
        }
        return \true;
    }
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }
    /**
     * @param iterable<string, mixed> $values
     */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return \true;
    }
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return \true;
    }
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }
    private function load(string $key): void
    {
        $stored = $this->cache->get($key);
        if (is_array($stored) && array_key_exists('value', $stored)) {
            $this->values[$key] = $stored['value'];
            $this->writtenAt[$key] = is_int($stored['written_at'] ?? null) ? $stored['written_at'] : null;
            return;
        }
        // Written without a timestamp, e.g. by an earlier version.
        $this->values[$key] = $stored;
        $this->writtenAt[$key] = null;
    }
}
