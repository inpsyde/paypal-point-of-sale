<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Adapter\Unleash;

/**
 * When the SDK registers the client with Unleash. Each registration is a request that
 * counts towards the request budget.
 */
enum RegistrationMode : string
{
    // Never register.
    case Off = 'off';
    // Register once per package version (see UnleashProjectBuilder::withPackageVersion()).
    case PerVersion = 'per-version';
}
