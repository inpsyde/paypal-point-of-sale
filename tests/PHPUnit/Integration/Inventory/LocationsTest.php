<?php
declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\API\Inventory\Locations;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Connection\ConnectionType;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Location\Location;
use Syde\PayPal\PointOfSale\PhpSdk\Map\InMemoryMap;
use Syde\PayPal\PointOfSale\PhpSdk\Map\OneToOneMapInterface;
use Syde\PayPal\PointOfSale\Test\AuthenticatedRestRequestTestCase;
use Psr\Container\ContainerInterface;

class LocationsTest extends AuthenticatedRestRequestTestCase
{

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
        parent::setUp();
    }

    public function testGetLocations()
    {
        $locations = $this->locations();
        $result = $locations->all();
        $this->assertContainsOnlyInstancesOf(Location::class, $result);
    }

    /** @return Locations */
    private function locations(): Locations
    {
        return $this->get('paypal-pos.sdk.api.inventory.locations');
    }
}
