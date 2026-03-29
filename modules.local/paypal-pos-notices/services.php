<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Notices;

use Psr\Container\ContainerInterface as C;
use Syde\PayPal\PointOfSale\Notices\Notice\Admin\CompleteOnboardingNotice;
use Syde\PayPal\PointOfSale\Notices\Notice\Admin\GlobalConnectionFailedNotice;
use Syde\PayPal\PointOfSale\Notices\Notice\Admin\IntegrationConnectionFailedNotice;
use Syde\PayPal\PointOfSale\Notices\Notice\NoticeDelegator;
use Syde\PayPal\PointOfSale\Notices\Notice\NoticeInterface;

return [
    'paypal-pos.notices.notification.notice.info.complete-onboarding' => static function (
        C $container
    ): NoticeInterface {
        return new CompleteOnboardingNotice(
            $container->get('paypal-pos.settings.is-integration-page'),
            $container->get('paypal-pos.settings.url')
        );
    },
    'paypal-pos.notices.notification.notice.error.global.auth-failed' => static function (
        C $container
    ): NoticeInterface {
        return new GlobalConnectionFailedNotice(
            $container->get('paypal-pos.settings.is-integration-page'),
            $container->get('paypal-pos.auth.is-failed'),
            $container->get('paypal-pos.settings.url')
        );
    },
    'paypal-pos.notices.notification.notice.error.integration.auth-failed' => static function (
        C $container
    ): NoticeInterface {
        return new IntegrationConnectionFailedNotice(
            $container->get('paypal-pos.settings.is-integration-page'),
            $container->get('paypal-pos.onboarding.api-auth-check'),
            $container->get('paypal-pos.settings.is-settings-save-request'),
            $container->get('paypal-pos.settings.account.link.api-key-creation-url')
        );
    },
    'paypal-pos.notices.notification.notices' => static function (C $container): array {
        return [
            $container->get('paypal-pos.notices.notification.notice.info.complete-onboarding'),
            $container->get('paypal-pos.notices.notification.notice.error.global.auth-failed'),
            $container->get('paypal-pos.notices.notification.notice.error.integration.auth-failed'),
        ];
    },
    'paypal-pos.notices.notification.delegator' => static function (C $container): NoticeDelegator {
        return new NoticeDelegator(
            ...$container->get('paypal-pos.notices.notification.notices')
        );
    },
];
