<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Onboarding;

interface OnboardingTransition
{
    const TO_WELCOME = 'to-welcome';
    const TO_API_CREDENTIALS = 'to-api-credentials';
    const TO_APP_CREDENTIALS = 'to-app-credentials';
    const TO_USER_CREDENTIALS = 'to-user-credentials';
    const TO_INVALID_CREDENTIALS = 'to-invalid-credentials';
    const TO_SYNC_PARAM_PRODUCTS = 'to-sync-param-products';
    const TO_SYNC_PARAM_VAT = 'to-sync-param-vat';
    const TO_SYNC_PROGRESS = 'to-sync-progress';
    const TO_SYNC_FINISHED = 'to-sync-finished';
    const TO_ONBOARDING_COMPLETED = 'to-onboarding-completed';
    const TO_UNHANDLED_ERROR = 'to-unhandled-error';
}
