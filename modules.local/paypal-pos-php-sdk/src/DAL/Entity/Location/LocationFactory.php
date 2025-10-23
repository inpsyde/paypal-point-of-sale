<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Location;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Location\Type\LocationType;

class LocationFactory
{
    /**
     * @param string $uuid
     * @param string $type
     * @param string $name
     * @param string|null $description
     * @param bool|null $default
     *
     * @return Location
     */
    public function create(
        string $uuid,
        string $type,
        string $name,
        ?string $description = null,
        ?bool $default = null
    ): Location {
        // TODO: Create LocationTypeValidator
        return new Location(
            $uuid,
            LocationType::get($type),
            $name,
            $description,
            $default
        );
    }
}
