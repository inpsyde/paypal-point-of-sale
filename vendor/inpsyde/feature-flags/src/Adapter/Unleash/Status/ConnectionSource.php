<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Adapter\Unleash\Status;

/**
 * Where the flags of a project currently come from.
 */
enum ConnectionSource : string
{
    // Nothing has been fetched from Unleash yet.
    case Unknown = 'unknown';
    // The last fetch succeeded.
    case Fresh = 'fresh';
    // The last fetch failed, and the stale copy of an earlier response is in use.
    case Stale = 'stale';
    // The last fetch failed or the SDK client could not be built, and there is no stale
    // copy. Every flag check returns the caller's default.
    case Defaults = 'defaults';
}
