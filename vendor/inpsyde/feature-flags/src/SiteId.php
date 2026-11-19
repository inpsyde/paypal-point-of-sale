<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\FeatureFlags;

/**
 * An ID for the current site that can't be traced back to it, for withTargetingId().
 *
 * It is an HMAC of the stored home URL, keyed with a salt of the site. home_url() would
 * follow the scheme of the current request and the per-language domains of multilingual
 * plugins, so the same site would get different IDs on different requests.
 */
final class SiteId
{
    /**
     * Requires wp_salt(), which is available from plugins_loaded on.
     *
     * @param string $saltScheme The wp_salt() scheme. Never use 'auth' or another login
     *     salt: the ID is published to every site that downloads the flag rules.
     */
    public static function forCurrentSite(string $saltScheme = 'feature_flags'): string
    {
        $homeUrl = strtolower(rtrim(trim((string) get_option('home')), '/'));
        $homeUrl = (string) preg_replace('#^[a-z][a-z0-9+.-]*://#', '', $homeUrl);
        return hash_hmac('sha256', $homeUrl, wp_salt($saltScheme));
    }
}
