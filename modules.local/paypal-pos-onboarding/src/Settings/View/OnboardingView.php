<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Onboarding\Settings\View;

interface OnboardingView
{

    /**
     * @return string
     */
    public function renderHeader(): string;

    /**
     * @return string
     */
    public function renderContent(): string;

    /**
     * @return string
     */
    public function renderProceedButton(): string;

    /**
     * @return string
     */
    public function renderBackButton(): string;
}
