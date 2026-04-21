<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Onboarding;

interface OnboardingTransition
{
    public const TO_WELCOME = 'to-welcome';
    public const TO_API_CREDENTIALS = 'to-api-credentials';
    public const TO_APP_CREDENTIALS = 'to-app-credentials';
    public const TO_USER_CREDENTIALS = 'to-user-credentials';
    public const TO_INVALID_CREDENTIALS = 'to-invalid-credentials';
    public const TO_SYNC_PARAM_PRODUCTS = 'to-sync-param-products';
    public const TO_SYNC_PARAM_VAT = 'to-sync-param-vat';
    public const TO_SYNC_PROGRESS = 'to-sync-progress';
    public const TO_SYNC_FINISHED = 'to-sync-finished';
    public const TO_ONBOARDING_COMPLETED = 'to-onboarding-completed';
    public const TO_UNHANDLED_ERROR = 'to-unhandled-error';
}
