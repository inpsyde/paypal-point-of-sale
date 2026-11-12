<?php

declare(strict_types=1);

use Syde\PayPal\PointOfSale\Webhooks\Bootstrap;
use Syde\PayPal\PointOfSale\Webhooks\Job\WebhookRegistrationJob;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class BootstrapTest extends BrainMonkeyWpTestCase
{
    /**
     * @dataProvider onboardingStates
     */
    public function testQueuesRegistrationOnlyOnceOnboardingIsCompleted(bool $onboarded, array $expected): void
    {
        $queued = [];
        $bootstrap = new Bootstrap(
            static function (string $type) use (&$queued): void {
                $queued[] = $type;
            },
            static function (): void {
            },
            static fn (): bool => $onboarded
        );

        $bootstrap->activate();

        self::assertSame($expected, $queued);
    }

    public function onboardingStates(): array
    {
        return [
            'onboarded' => [true, [WebhookRegistrationJob::TYPE]],
            'still onboarding' => [false, []],
        ];
    }
}
