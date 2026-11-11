<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Adapter\Unleash;

use Syde\Vendor\Zettle\Unleash\Client\Configuration\Context;
use Syde\Vendor\Zettle\Unleash\Client\Configuration\UnleashContext;
use Syde\Vendor\Zettle\Unleash\Client\ContextProvider\UnleashContextProvider;
use Syde\Vendor\Zettle\Unleash\Client\Enum\ContextField;
/**
 * Builds the context flags are evaluated against: the site's targeting ID, the package version
 * and the consumer's properties, nothing about the visitor, the user or the server.
 *
 * The SDK falls back to REMOTE_ADDR, session_id() and gethostname() for missing values, so the
 * IP address, session ID and hostname are set to empty strings rather than left out.
 *
 * @internal Built by UnleashProjectBuilder.
 */
final class ContextProvider implements UnleashContextProvider
{
    public const SITE_ID = 'siteId';
    public const PACKAGE_VERSION = 'packageVersion';
    /**
     * Names the SDK resolves itself or stores in the custom context, so a custom property
     * with one of them would be ignored, overwritten or break constraints.
     */
    private const SDK_FIELDS = [ContextField::USER_ID, ContextField::SESSION_ID, ContextField::IP_ADDRESS, ContextField::ENVIRONMENT, ContextField::HOSTNAME, ContextField::CURRENT_TIME];
    /**
     * @param array<string, string> $properties
     */
    public function __construct(private readonly string $project, private readonly string $targetingId, private readonly string $packageVersion = '0.0.0', private readonly array $properties = [])
    {
    }
    /**
     * Whether a property name is reserved for the SDK's or this package's context fields.
     */
    public static function isReserved(string $name): bool
    {
        return in_array($name, [...self::SDK_FIELDS, self::SITE_ID, self::PACKAGE_VERSION], \true);
    }
    public function getContext(): Context
    {
        $customContext = $this->properties;
        $customContext[self::SITE_ID] = $this->targetingId;
        $customContext[self::PACKAGE_VERSION] = $this->packageVersion;
        $unfiltered = ['userId' => $this->targetingId, 'ipAddress' => '', 'sessionId' => '', 'hostname' => '', 'customContext' => $customContext, 'environment' => null];
        $hookBase = UnleashProject::HOOK_BASE;
        $context = self::filter("{$hookBase}.context", $unfiltered);
        $context = self::filter("{$hookBase}.{$this->project}.context", $context);
        return new UnleashContext(self::stringOrNull($context['userId']), self::stringOrNull($context['ipAddress']), self::stringOrNull($context['sessionId']), self::customContext($context['customContext']), self::stringOrNull($context['hostname']), self::stringOrNull($context['environment']));
    }
    /**
     * A filter callback that returns no array, e.g. by forgetting the return, leaves the
     * context unchanged, and keys it removes keep their previous values. The same holds for
     * the custom properties, so a callback that returns its own customContext doesn't drop
     * siteId and packageVersion.
     *
     * @param non-empty-string $hook
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private static function filter(string $hook, array $context): array
    {
        $filtered = apply_filters($hook, $context);
        if (!is_array($filtered)) {
            return $context;
        }
        if (is_array($filtered['customContext'] ?? null) && is_array($context['customContext'])) {
            $filtered['customContext'] += $context['customContext'];
        }
        return $filtered + $context;
    }
    private static function stringOrNull(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }
    /**
     * Cleans up the custom context after the filters: build() has validated the properties,
     * but filters may add anything. Values that aren't scalar and the SDK's field names are
     * dropped.
     *
     * @return array<string, string>
     */
    private static function customContext(mixed $values): array
    {
        $result = [];
        foreach (is_array($values) ? $values : [] as $name => $value) {
            $name = (string) $name;
            if (is_scalar($value) && !in_array($name, self::SDK_FIELDS, \true)) {
                $result[$name] = (string) $value;
            }
        }
        return $result;
    }
}
