<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks;

use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\JobRecord;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\Job\WebhookRegistrationJob;
return [
    // Registration calls the PayPal POS API, so immediate retries don't help against rate limits
    // or short outages. With the queue's 3 retries this backs off 2, 8 and 32 minutes.
    'inpsyde.queue.failed.retry.delay' => static function (callable $previous): callable {
        return static function (JobRecord $record) use ($previous): int {
            return $record->job()->type() === WebhookRegistrationJob::TYPE ? 120 * 4 ** $record->context()->retryCount() : (int) $previous($record);
        };
    },
];
