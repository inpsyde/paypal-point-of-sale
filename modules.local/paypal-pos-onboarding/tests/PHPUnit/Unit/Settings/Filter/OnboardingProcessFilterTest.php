<?php

declare(strict_types=1);

use Syde\PayPal\PointOfSale\Onboarding\OnboardingState as S;
use Syde\PayPal\PointOfSale\Onboarding\Settings\Filter\OnboardingProcessFilter;
use MonkeryTestCase\BrainMonkeyWpTestCase;

class OnboardingProcessFilterTest extends BrainMonkeyWpTestCase
{
    private function settings(): array
    {
        return [
            'sync_params' => ['type' => 'title'],
            'order_sync_enabled' => ['type' => 'select', 'zettle_visible_after_onboarding' => true],
        ];
    }

    public function testFlaggedFieldsStayVisibleOnceOnboardingIsComplete(): void
    {
        $settings = (new OnboardingProcessFilter(S::ONBOARDING_COMPLETED))->filter($this->settings());

        self::assertArrayNotHasKey('zettle_hide', $settings['order_sync_enabled']);
        self::assertTrue($settings['sync_params']['zettle_hide']);
    }

    public function testFlaggedFieldsAreHiddenDuringOnboarding(): void
    {
        $settings = (new OnboardingProcessFilter(S::SYNC_PARAM_PRODUCTS))->filter($this->settings());

        self::assertTrue($settings['order_sync_enabled']['zettle_hide']);
        // Disabled, so a step's own input for the same setting is the only value posted.
        self::assertArrayHasKey('disabled', $settings['order_sync_enabled']['custom_attributes']);
        self::assertArrayNotHasKey('disabled', $settings['sync_params']['custom_attributes']);
    }
}
