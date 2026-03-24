<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Test;

use Syde\PayPal\PointOfSale\Container\ArrayContainer;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Vat\Vat;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Provider\Organization\OrganizationProvider;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Provider\Organization\RestOrganizationProvider;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Provider\Vat\VatProvider;
use Syde\PayPal\PointOfSale\PhpSdk\Map\InMemoryMap;
use Syde\PayPal\PointOfSale\PhpSdk\Map\OneToOneMapInterface;
use Syde\PayPal\PointOfSale\PhpSdk\Validator\LocalImageValidator;
use Syde\PayPal\PointOfSale\Test\DataProvider\WcProductSampleData;
use Mockery;
use Psr\Container\ContainerInterface;

/**
 * phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv
 */
class ZettleEntityCrudTestCase extends AuthenticatedRestRequestTestCase
{

    protected function setUp(): void
    {
        WcProductSampleData::flush();

        $this->injectFactory(
            'paypal-pos.sdk.validator.image',
            function (ContainerInterface $container): LocalImageValidator {
                $mock = \Mockery::mock(LocalImageValidator::class)->makePartial();

                $mock->shouldReceive('validate')->andReturnTrue();

                return $mock;
            }
        );

        $this->injectFactory(
            'paypal-pos.sdk.id-map.product',
            function (ContainerInterface $container): OneToOneMapInterface {
                return new InMemoryMap();
            }
        );

        putenv('IZETTLE_PLACEHOLDER_IMAGES_ENABLED=1');

        $this->injectFactory(
            'paypal-pos.sdk.id-map.variant',
            function (ContainerInterface $container): OneToOneMapInterface {
                return new InMemoryMap();
            }
        );

        $this->injectFactory(
            'paypal-pos.sdk.id-map.image',
            function (ContainerInterface $container): OneToOneMapInterface {
                return new InMemoryMap();
            }
        );

        $this->injectFactory(
            'paypal-pos.sdk.dal.provider.vat.zettle',
            function (ContainerInterface $container): VatProvider {
                $mock = Mockery::mock(VatProvider::class);
                $mock->shouldReceive('provide')->andReturn(new Vat(20.0));

                return $mock;
            }
        );

        $this->injectFactory(
            'paypal-pos.sdk.config.woocommerce-config',
            function () {
                return new ArrayContainer(
                    [
                        'weight_unit' => 'kg',
                        'currency' => 'GBP',
                    ]
                );
            }
        );

        $this->injectFactory(
            'paypal-pos.sdk.dal.provider.organization',
            function (ContainerInterface $container): OrganizationProvider {
                return new RestOrganizationProvider(
                    $container->get('paypal-pos.sdk.api.oauth.organizations')
                );
            }
        );

        parent::setUp();
    }
}
