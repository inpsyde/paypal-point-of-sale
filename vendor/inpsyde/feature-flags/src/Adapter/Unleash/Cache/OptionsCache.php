<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Adapter\Unleash\Cache;

use DateInterval;
use Syde\Vendor\Zettle\Psr\SimpleCache\CacheInterface;
use Syde\Vendor\Zettle\Unleash\Client\DTO\DefaultFeature;
use Syde\Vendor\Zettle\Unleash\Client\Unleash;
/**
 * Stores the Unleash SDK cache in wp_options.
 *
 * Option names contain a fingerprint of the connection (URL, token, app name), the SDK's
 * feature class name (which changes with a php-scoper prefix), the SDK version and
 * CACHE_SCHEMA_VERSION. When the fingerprint changes, the previous entries are deleted.
 *
 * The flag list holds serialized SDK objects. A newer SDK can add properties to them, and
 * entries written by an older SDK then fail on read, so an SDK update discards the flag list
 * and costs one fetch per site. The stale copy is raw JSON and is carried over.
 * The plugin version is deliberately not part of the fingerprint, so releases that don't
 * update the SDK cause no additional requests to Unleash.
 *
 * When the flag list has expired, only one request refetches it: that request gets a miss,
 * concurrent ones keep getting the expired list until the new one is stored. When there is no
 * flag list yet, e.g. on first use, concurrent requests get an empty one, so their flags return
 * the callers' defaults until the new one is stored.
 *
 * The flag list is read on every flag check, so each instance keeps it in memory once read.
 */
final class OptionsCache implements CacheInterface
{
    /**
     * Bump this only when the format of stored entries changes.
     */
    public const CACHE_SCHEMA_VERSION = 1;
    private const PREFIX = 'inpsyde_ff_unleash_';
    // The SDK's flag list (read on every request) and its stale copy of the last response.
    private const KEY_FEATURES = 'unleash.client.feature.list';
    private const KEY_FEATURES_RESPONSE = 'unleash.client.feature.response';
    private const KEY_METRICS_BUCKET = 'unleash.client.metrics.bucket';
    // Keys of this package, stored next to the SDK's.
    public const KEY_CONNECTION_STATUS = 'feature-flags.connection-status';
    public const KEY_REGISTERED_VERSION = 'feature-flags.registered-version';
    private const KEY_REFRESH_LOCK = 'feature-flags.refresh-lock';
    private const KEY_METRICS_SENT = 'feature-flags.metrics-sent';
    // Read on every request, and written at most every few minutes (see MetricsBuffer).
    private const AUTOLOADED_KEYS = [self::KEY_FEATURES, self::KEY_METRICS_BUCKET, self::KEY_REGISTERED_VERSION];
    // All cache keys start with one of these. purge() matches them to tell a project's options
    // apart from those of a project whose name continues with "_{12 hex characters}".
    private const KEY_PREFIXES = ['unleash.client.', 'feature-flags.'];
    // A refresh lock older than this counts as abandoned, e.g. after a fatal error.
    private const REFRESH_LOCK_TIMEOUT = 60;
    // Projects with longer names are hashed, so option names stay within 191 characters.
    private const MAX_PROJECT_LENGTH = 64;
    private const FINGERPRINT_LENGTH = 12;
    private readonly string $fingerprint;
    private bool $indexChecked = \false;
    /**
     * The flag list as read or written by this instance, or null before.
     */
    private mixed $features = null;
    /**
     * @var \Closure(): int
     */
    private readonly \Closure $clock;
    /**
     * @var (\Closure(): void)|null
     */
    private ?\Closure $responseStoredListener = null;
    /**
     * @param string $connectionId See OptionsCache::connectionId()
     * @param (\Closure(): int)|null $clock Returns the current Unix timestamp. Defaults to time().
     * @param float $ttlJitter Randomises the TTL of the flag list by up to this ratio in either
     *     direction, so sites don't refresh in sync.
     */
    public function __construct(private readonly string $project, private readonly string $connectionId, ?\Closure $clock = null, private readonly float $ttlJitter = 0.0)
    {
        $this->clock = $clock ?? time(...);
        $this->fingerprint = substr(hash('sha256', implode('|', [$connectionId, DefaultFeature::class, Unleash::SDK_VERSION, (string) self::CACHE_SCHEMA_VERSION])), 0, self::FINGERPRINT_LENGTH);
    }
    /**
     * Identifies a connection to Unleash. The API token is only used as a hash.
     */
    public static function connectionId(string $appUrl, string $apiToken, string $appName): string
    {
        return hash('sha256', implode('|', [self::normalizeUrl($appUrl), hash('sha256', $apiToken), $appName]));
    }
    /**
     * Deletes all options of a project on the current site, including those of earlier
     * fingerprints and of the salt-based naming scheme. Meant for uninstall routines.
     */
    public static function purge(string $project): void
    {
        $prefix = self::PREFIX . self::projectSegment($project) . '_';
        $keyPrefixes = implode('|', array_map(static fn(string $keyPrefix): string => preg_quote($keyPrefix, '/'), self::KEY_PREFIXES));
        $pattern = '/^' . preg_quote($prefix, '/') . '(index$|[0-9a-f]{' . self::FINGERPRINT_LENGTH . "}_({$keyPrefixes}))/";
        foreach (self::optionNames($prefix) as $name) {
            if (preg_match($pattern, $name)) {
                delete_option($name);
            }
        }
        self::deleteLegacyOptions($project);
    }
    public function fingerprint(): string
    {
        return $this->fingerprint;
    }
    /**
     * @internal Called with every response the SDK stores as its stale copy, which it only
     *     does for successful fetches.
     *
     * @param \Closure(): void $listener
     */
    public function onResponseStored(\Closure $listener): void
    {
        $this->responseStoredListener = $listener;
    }
    /**
     * @phpstan-impure Deletes unreadable or expired values and takes the refresh lock.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if ($key !== self::KEY_FEATURES) {
            return $this->read($key, $default);
        }
        if ($this->features === null) {
            $features = $this->readFeatures();
            if ($features === null) {
                return $default;
            }
            $this->features = $features;
        }
        return $this->features;
    }
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->checkIndex();
        $data = ['value' => $value, 'expires_at' => $this->calculateExpiration($ttl, $key === self::KEY_FEATURES)];
        // update_option() returns false when the value is unchanged, which is not a failure.
        update_option($this->optionName($key), $data, in_array($key, self::AUTOLOADED_KEYS, \true));
        if ($key === self::KEY_FEATURES) {
            $this->features = $value;
            delete_option($this->optionName(self::KEY_REFRESH_LOCK));
        }
        if ($key === self::KEY_FEATURES_RESPONSE && $this->responseStoredListener) {
            ($this->responseStoredListener)();
        }
        return \true;
    }
    public function delete(string $key): bool
    {
        if ($key === self::KEY_FEATURES) {
            $this->features = null;
        }
        delete_option($this->optionName($key));
        return \true;
    }
    public function clear(): bool
    {
        $this->features = null;
        $this->deleteFingerprint($this->fingerprint);
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
    /**
     * Reads a value, or returns $default when it is missing, unreadable or expired. Unreadable
     * and expired values are deleted.
     */
    private function read(string $key, mixed $default): mixed
    {
        $this->checkIndex();
        $name = $this->optionName($key);
        $data = get_option($name, null);
        if ($data === null || $data === \false) {
            return $default;
        }
        if (!is_array($data) || !array_key_exists('value', $data) || !array_key_exists('expires_at', $data) || self::isUnreadable($data['value'])) {
            delete_option($name);
            return $default;
        }
        if ($data['expires_at'] !== null && ($this->clock)() > $data['expires_at']) {
            delete_option($name);
            return $default;
        }
        return $data['value'];
    }
    /**
     * Reads the flag list. Returns null when this request has to fetch it. While another
     * request fetches it, returns the expired list, or an empty one if there is none.
     */
    private function readFeatures(): mixed
    {
        $this->checkIndex();
        $data = get_option($this->optionName(self::KEY_FEATURES), null);
        $readable = is_array($data) && array_key_exists('value', $data) && array_key_exists('expires_at', $data) && !self::isUnreadable($data['value']);
        if (!$readable) {
            if ($data !== null && $data !== \false) {
                delete_option($this->optionName(self::KEY_FEATURES));
            }
            return $this->acquireRefreshLock() ? null : [];
        }
        if ($data['expires_at'] !== null && ($this->clock)() > $data['expires_at']) {
            // The expired list stays, to be served while another request refetches.
            return $this->acquireRefreshLock() ? null : $data['value'];
        }
        return $data['value'];
    }
    /**
     * Compares the stored fingerprint with the current one once per instance, and cleans
     * up entries of a previous fingerprint or of the salt-based naming scheme.
     */
    private function checkIndex(): void
    {
        if ($this->indexChecked) {
            return;
        }
        $this->indexChecked = \true;
        $indexName = $this->indexName();
        $index = get_option($indexName, null);
        $previousFingerprint = is_array($index) ? (string) ($index['fingerprint'] ?? '') : '';
        if ($previousFingerprint === $this->fingerprint) {
            return;
        }
        if ($previousFingerprint === '') {
            self::deleteLegacyOptions($this->project);
        }
        if ($previousFingerprint !== '') {
            $this->migrateFrom($previousFingerprint, (string) ($index['connection'] ?? ''));
        }
        update_option($indexName, ['fingerprint' => $this->fingerprint, 'connection' => $this->connectionId], \true);
    }
    /**
     * Keeps the stale copy of the last response when only the stored format or the class
     * names changed, so an Unleash outage right after an update still has a fallback.
     * A different connection never carries anything over.
     */
    private function migrateFrom(string $previousFingerprint, string $previousConnectionId): void
    {
        if ($previousConnectionId === $this->connectionId) {
            $staleCopy = get_option($this->optionName(self::KEY_FEATURES_RESPONSE, $previousFingerprint), null);
            if (is_array($staleCopy) && is_string($staleCopy['value'] ?? null)) {
                update_option($this->optionName(self::KEY_FEATURES_RESPONSE), $staleCopy, \false);
            }
        }
        $this->deleteFingerprint($previousFingerprint);
    }
    /**
     * Claims sending the metrics, unless a request has claimed it less than $interval seconds
     * ago. Like the refresh lock, add_option() decides between concurrent requests.
     */
    public function claimMetricsSend(int $interval): bool
    {
        return $this->claim(self::KEY_METRICS_SENT, $interval);
    }
    /**
     * add_option() fails if the option exists, so only one request gets the lock.
     */
    private function acquireRefreshLock(): bool
    {
        return $this->claim(self::KEY_REFRESH_LOCK, self::REFRESH_LOCK_TIMEOUT);
    }
    /**
     * Stores the current time under the key, unless it holds a time less than $timeout
     * seconds ago.
     */
    private function claim(string $key, int $timeout): bool
    {
        $name = $this->optionName($key);
        $now = ($this->clock)();
        if (add_option($name, $now, '', \false)) {
            return \true;
        }
        // Read back from the database, the timestamp is a string.
        $claimedAt = get_option($name, 0);
        if (is_numeric($claimedAt) && $now - (int) $claimedAt < $timeout) {
            return \false;
        }
        update_option($name, $now, \false);
        return \true;
    }
    private function deleteFingerprint(string $fingerprint): void
    {
        $prefix = self::PREFIX . self::projectSegment($this->project) . "_{$fingerprint}_";
        foreach (self::optionNames($prefix) as $name) {
            delete_option($name);
        }
    }
    private function optionName(string $key, ?string $fingerprint = null): string
    {
        $fingerprint ??= $this->fingerprint;
        return self::PREFIX . self::projectSegment($this->project) . "_{$fingerprint}_{$key}";
    }
    private function indexName(): string
    {
        return self::PREFIX . self::projectSegment($this->project) . '_index';
    }
    private static function projectSegment(string $project): string
    {
        if (strlen($project) <= self::MAX_PROJECT_LENGTH) {
            return $project;
        }
        return substr(hash('sha256', $project), 0, self::FINGERPRINT_LENGTH);
    }
    /**
     * Deletes the options of the salt-based naming scheme used before 2.0.0:
     * inpsyde_ff_unleash_{project}_{salt}_{key} and inpsyde_feature_flags_unleash_{project}_salt.
     * The salt was a 10-digit Unix timestamp, so it can't be mistaken for a fingerprint.
     */
    private static function deleteLegacyOptions(string $project): void
    {
        $prefix = self::PREFIX . "{$project}_";
        $pattern = '/^' . preg_quote($prefix, '/') . '\d{10}_unleash\.client\./';
        foreach (self::optionNames($prefix) as $name) {
            if (preg_match($pattern, $name)) {
                delete_option($name);
            }
        }
        delete_option("inpsyde_feature_flags_unleash_{$project}_salt");
    }
    /**
     * Trailing slashes and the case of the scheme and host don't make a different connection.
     */
    private static function normalizeUrl(string $url): string
    {
        return (string) preg_replace_callback('#^[a-z][a-z0-9+.-]*://[^/?\#]+#i', static fn(array $match): string => strtolower($match[0]), rtrim(trim($url), '/'));
    }
    /**
     * @return list<string>
     */
    private static function optionNames(string $prefix): array
    {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $names = $wpdb->get_col($wpdb->prepare("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like($prefix) . '%'));
        return array_values(array_map(strval(...), (array) $names));
    }
    /**
     * Entries whose classes no longer exist (e.g. after a php-scoper prefix change)
     * unserialize to __PHP_Incomplete_Class and can't be used by the SDK.
     */
    private static function isUnreadable(mixed $value): bool
    {
        if ($value instanceof \__PHP_Incomplete_Class) {
            return \true;
        }
        if (is_array($value)) {
            foreach ($value as $item) {
                if ($item instanceof \__PHP_Incomplete_Class) {
                    return \true;
                }
            }
        }
        return \false;
    }
    private function calculateExpiration(null|int|DateInterval $ttl, bool $withJitter): ?int
    {
        if ($ttl === null) {
            return null;
        }
        $seconds = is_int($ttl) ? $ttl : (new \DateTimeImmutable('@0'))->add($ttl)->getTimestamp();
        if ($withJitter && $this->ttlJitter > 0) {
            $random = random_int(-1000000, 1000000) / 1000000;
            $seconds = (int) round($seconds * (1 + $this->ttlJitter * $random));
        }
        return ($this->clock)() + $seconds;
    }
}
