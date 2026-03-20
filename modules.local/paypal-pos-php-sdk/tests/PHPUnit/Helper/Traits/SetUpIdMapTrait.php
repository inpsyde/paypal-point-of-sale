<?php
declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Tests\Traits;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Connection\ConnectionType;
use Syde\PayPal\PointOfSale\PhpSdk\Map\InMemoryMap;
use Syde\PayPal\PointOfSale\PhpSdk\Map\OneToOneMapInterface;
use Syde\Vendor\Zettle\Psr\Container\ContainerInterface;

trait SetUpIdMapTrait
{

    abstract protected function injectFactory(string $key, callable $factory);

    abstract protected function injectExtension(string $key, callable $factory);

    protected function setUpIdMap(string $entity, array $map = [])
    {
        $this->injectFactory(
            'paypal-pos.sdk.id-map.'.$entity,
            function (ContainerInterface $container) use ($map): OneToOneMapInterface {
                return new InMemoryMap($map);
            }
        );
    }

    protected function setUpNoopIdMaps()
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
    }
}
