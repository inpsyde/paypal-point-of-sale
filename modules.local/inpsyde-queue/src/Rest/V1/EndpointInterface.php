<?php

declare(strict_types=1);

# -*- coding: utf-8 -*-

namespace Inpsyde\Queue\Rest\V1;

use WP_REST_Request;
use WP_REST_Response;

interface EndpointInterface
{
    /**
     * @return string
     */
    public function methods(): string;

    /**
     * @return string
     */
    public function version(): string;

    /**
     * @return string
     */
    public function route(): string;

    /**
     * @return bool
     */
    public function permissionCallback(): bool;

    /**
     * @return array
     */
    public function args(): array;

    /**
     * Handle Endpoint Request
     *
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     */
    public function handleRequest(WP_REST_Request $request): WP_REST_Response;
}
