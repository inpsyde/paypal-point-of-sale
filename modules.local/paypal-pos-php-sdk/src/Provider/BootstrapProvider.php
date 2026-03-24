<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Provider;

use Syde\PayPal\PointOfSale\PhpSdk\Bootstrap;
use Syde\PayPal\PointOfSale\Provider;
use Psr\Container\ContainerInterface as C;

class BootstrapProvider implements Provider
{

    /**
     * @var Bootstrap
     */
    private $boostrap;

    /**
     * BootstrapProvider constructor.
     *
     * @param Bootstrap $boostrap
     */
    public function __construct(Bootstrap $boostrap)
    {
        $this->boostrap = $boostrap;
    }

    /**
     * @inheritDoc
     */
    public function boot(C $container): bool
    {
        add_action(
            'paypal-point-of-sale.migrate',
            function () {
                $this->boostrap->activate();
            }
        );
        add_action(
            'paypal-point-of-sale.activate',
            function () {
                $this->boostrap->activate();
            },
            5
        );

        return true;
    }
}
