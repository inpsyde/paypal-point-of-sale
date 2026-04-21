<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Onboarding;

interface OnboardingState
{
    public const WELCOME = 'welcome';
    public const API_CREDENTIALS = 'api-credentials';
    public const INVALID_CREDENTIALS = 'invalid-credentials';
    public const SYNC_PARAM_PRODUCTS = 'sync-param-products';
    public const SYNC_PARAM_VAT = 'sync-parameters-vat';
    public const SYNC_PROGRESS = 'sync-progress';
    public const SYNC_FINISHED = 'sync-finished';
    public const ONBOARDING_COMPLETED = 'onboarding-completed';
    public const UNHANDLED_ERROR = 'unhandled-error';
}
