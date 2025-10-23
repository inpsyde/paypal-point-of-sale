<?php

declare(strict_types=1);

namespace Inpsyde\Queue\Queue\Runner;

use Inpsyde\Queue\Processor\QueueProcessor;
use WP_Http_Cookie;

/**
 * Class AsyncRequestRunner
 *
 * Triggers the internal REST endpoint with a non-blocking HTTP request
 * so that the queue is processed without blocking the current request
 *
 * @package Inpsyde\Queue\Queue\Runner
 */
class AsyncRequestRunner implements Runner
{

    public const DEFAULT_TIMEOUT = 0.01;
    public const DEFAULT_IS_BLOCKING = false;

    /**
     * @var string Path (not the full URL!) to our REST endpoint
     */
    private $restPath;

    /**
     * AsyncRequestRunner constructor.
     *
     * @param string $restPath
     */
    public function __construct(string $restPath)
    {
        $this->restPath = $restPath;
    }

    /**
     * Craft and dispatch a non-blocking HTTP request with the current authentication headers
     *
     * @param QueueProcessor $queueProcessor
     */
    public function initialize(QueueProcessor $queueProcessor): void
    {
        if (!$this->shouldTrigger()) {
            return;
        }

        $cookies = [];

        foreach (
            [
            AUTH_COOKIE,
            SECURE_AUTH_COOKIE,
            LOGGED_IN_COOKIE,
            TEST_COOKIE,
            ] as $cookieName
        ) {
            $value = filter_input(INPUT_COOKIE, $cookieName);

            if (!$value) {
                continue;
            }

            assert(is_string($cookieName));

            $cookies[] = new WP_Http_Cookie(
                [
                    'name' => $cookieName,
                    'value' => $value,
                ]
            );
        }

        $headers = [
            'X-WP-Nonce' => wp_create_nonce('wp_rest'),
        ];

        $authorization = filter_input(INPUT_SERVER, 'HTTP_AUTHORIZATION');

        if ($authorization) {
            $headers['Authorization'] = $authorization;
        }

        $url = add_query_arg(
            [
                'executionTime' => 20,
            ],
            rest_url($this->restPath)
        );

        $queueRequest = [
            'url' => $url,
            'args' => [
                'headers' => $headers,
                'cookies' => $cookies,
                'timeout' => self::DEFAULT_TIMEOUT,
                'blocking' => self::DEFAULT_IS_BLOCKING,
                /** This filter is documented in wp-includes/class-wp-http-streams.php */
                'sslverify' => apply_filters('https_local_ssl_verify', false),
            ],
        ];
        // Suppress errors since we've seen some weird notifes only if wp-cli is installed
        @wp_remote_get($queueRequest['url'], $queueRequest['args']);
    }

    /**
     * Let's not act if we are already doing REST or an ajax call
     * just to sidestep potential infinite loops
     *
     * @return bool
     */
    private function shouldTrigger(): bool
    {
        return !defined('REST_REQUEST') && !wp_doing_ajax();
    }
}
