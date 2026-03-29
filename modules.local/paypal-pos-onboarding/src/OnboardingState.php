<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Onboarding;

interface OnboardingState
{
    const WELCOME = 'welcome';
    const API_CREDENTIALS = 'api-credentials';
    const INVALID_CREDENTIALS = 'invalid-credentials';
    const SYNC_PARAM_PRODUCTS = 'sync-param-products';
    const SYNC_PARAM_VAT = 'sync-parameters-vat';
    const SYNC_PROGRESS = 'sync-progress';
    const SYNC_FINISHED = 'sync-finished';
    const ONBOARDING_COMPLETED = 'onboarding-completed';
    const UNHANDLED_ERROR = 'unhandled-error';
}
