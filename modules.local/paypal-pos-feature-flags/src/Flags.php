<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\FeatureFlags;

use Inpsyde\FeatureFlags\Adapter\Unleash\UnleashProjectBuilder;
use Inpsyde\FeatureFlags\FeatureFlags;
use Psr\Log\LoggerInterface;

/**
 * The PayPal POS feature flags. Checks go through the project's filter, so snippets and
 * support can override a flag with a later callback.
 *
 * The project is registered with the library on the first check, so requests that check no
 * flag don't pay for it.
 */
final class Flags
{
    private bool $connected = false;

    /**
     * @param UnleashProjectBuilder $builder The request settings and the context. The URL and
     *     token are set here, so they live in one place.
     */
    public function __construct(
        private readonly string $project,
        private readonly string $url,
        private readonly string $token,
        private readonly UnleashProjectBuilder $builder,
        private readonly LoggerInterface $logger
    ) {
    }

    public function isOn(string $flag, bool $default = false): bool
    {
        $this->connect();

        return (bool) apply_filters(
            FeatureFlags::HOOK_BASE . ".{$this->project}",
            $default,
            $flag
        );
    }

    /**
     * Connects on the first check through the filters, for code that doesn't use isOn().
     */
    public function initialize(): void
    {
        $hookBase = FeatureFlags::HOOK_BASE;

        // Priority 0: the callbacks the library adds at 10 still run for the same check.
        add_filter(
            "{$hookBase}.{$this->project}",
            function (mixed $value): mixed {
                $this->connect();

                return $value;
            },
            0
        );
        add_filter(
            $hookBase,
            function (mixed $value, mixed $flag = null, mixed $project = null): mixed {
                if ($project === $this->project) {
                    $this->connect();
                }

                return $value;
            },
            0,
            3
        );
    }

    /**
     * Registers the project with the library, once per request. Without a connection, or with
     * an invalid one, no project is registered and checks return their default. A
     * configuration error must not take the shop down.
     */
    private function connect(): void
    {
        if ($this->connected) {
            return;
        }
        $this->connected = true;

        if ($this->url === '' && $this->token === '') {
            return;
        }
        if ($this->url === '' || $this->token === '') {
            // The built-in token must never go to an overridden URL, and vice versa.
            $this->logOncePerDay('The URL and the token must be set together.');

            return;
        }

        try {
            FeatureFlags::registerProject(
                $this->builder->withAppUrl($this->url)->withApiToken($this->token)->build()
            );
        } catch (\InvalidArgumentException $exception) {
            $this->logOncePerDay($exception->getMessage());
        }
    }

    /**
     * A configuration error persists on every request until it is fixed.
     */
    private function logOncePerDay(string $message): void
    {
        $key = 'paypal_pos_feature_flags_error_' . md5($message);
        if (get_transient($key) !== false) {
            return;
        }
        set_transient($key, 1, DAY_IN_SECONDS);

        $this->logger->error('Feature flags are not available: {message}', ['message' => $message]);
    }
}
