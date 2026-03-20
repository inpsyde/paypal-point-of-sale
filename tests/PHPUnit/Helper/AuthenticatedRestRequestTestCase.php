<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Test;

use Inpsyde\Queue\Queue\Job\EphemeralJobRepository;
use Inpsyde\Queue\Queue\Job\JobRepository;
use Syde\PayPal\PointOfSale\Auth\OAuth\EphemeralTokenStorage;
use Syde\PayPal\PointOfSale\Container\ArrayContainer;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Connection\ConnectionType;
use Syde\PayPal\PointOfSale\PhpSdk\Map\InMemoryMap;
use Syde\PayPal\PointOfSale\PhpSdk\Map\OneToOneMapInterface;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;

class AuthenticatedRestRequestTestCase extends ModuleContainerAwareTestCase
{
    /**
     * @var bool
     */
    protected $isMultisite = true;

    /**
     * @var int
     */
    protected $currentSiteId = 1;

    protected function setUp(): void
    {
        foreach ([
            ConnectionType::IMAGE,
            ConnectionType::PRODUCT,
            ConnectionType::VARIANT,
        ] as $type) {
            $this->injectFactory(
                'paypal-pos.sdk.id-map.'.$type,
                function (ContainerInterface $container): OneToOneMapInterface {
                    return new InMemoryMap([]);
                }
            );
        }

        $tokenStorage = new EphemeralTokenStorage();

        $this->injectFactory(
            'paypal-pos.oauth.token-storage',
            function (ContainerInterface $container) use ($tokenStorage) {
                return $tokenStorage;
            }
        );

        $this->injectFactory(
            'inpsyde.queue.repository',
            function (): JobRepository {
                return new EphemeralJobRepository();
            }
        );
        $this->injectFactory(
            'paypal-pos.is-multisite',
            function (): bool {
                return $this->isMultisite;
            }
        );
        $this->injectFactory(
            'paypal-pos.current-site-id',
            function (): int {
                return $this->currentSiteId;
            }
        );
        $this->injectExtension(
            'paypal-pos.oauth.credentials.parent',
            function ($previous, ContainerInterface $container) {
                return new EnvMapCredentialsContainer();
            }
        );
        $this->injectFactory(
            'paypal-pos.settings',
            function (ContainerInterface $container) {
                return new ArrayContainer([]);
            }
        );
        $this->injectFactory(
            'paypal-pos.logger.woocommerce',
            function (ContainerInterface $container) {
                return new NullLogger();
            }
        );
        $this->injectFactory(
            'inpsyde.http-client.mode',
            function () {
                return '';
            }
        );

        parent::setUp();
    }
}
