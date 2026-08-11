<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding\Rest;

use WP_REST_Request;
use WP_REST_Response;
interface EndpointInterface
{
    public function methods(): string;
    public function version(): string;
    public function route(): string;
    public function permissionCallback(): bool;
    public function args(): array;
    public function handleRequest(WP_REST_Request $request): WP_REST_Response;
}
