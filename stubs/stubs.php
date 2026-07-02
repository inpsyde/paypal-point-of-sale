<?php

if (!defined('ABSPATH')) {
	define('ABSPATH', '/var/www/html/');
}

if (!function_exists('wp_cache_flush_runtime')) {
    /**
     * Removes all cache items from the in-memory runtime cache.
     *
     * @return bool True on success, false on failure.
     * @see WP_Object_Cache::flush()
     *
     * @since 6.0.0
     *
     */
    function wp_cache_flush_runtime()
    {
    }
}
