<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks;

return static function (): WebhookModule {
    return new WebhookModule();
};
