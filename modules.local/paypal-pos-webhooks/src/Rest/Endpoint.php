<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks\Rest;

use WP_REST_Request;

interface Endpoint
{

    /**
     * Return supported Endpoint CRUD Methods
     *
     * @return string[]
     */
    public function methods(): array;

    /**
     * Delegate Webhook Handlers
     *
     * @param WP_REST_Request $request
     *
     * @return array<string, int>
     */
    public function callback(WP_REST_Request $request): array;
}
