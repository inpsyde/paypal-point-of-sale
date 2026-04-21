<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Location;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Location\Type\LocationType;
class Location
{
    private string $uuid;
    private LocationType $type;
    private string $name;
    private ?string $description = null;
    private ?bool $default = null;
    /**
     * Location constructor.
     *
     * @param string $uuid
     * @param LocationType $type
     * @param string $name
     * @param string|null $description
     * @param bool|null $default
     */
    public function __construct(string $uuid, LocationType $type, string $name, ?string $description = null, ?bool $default = null)
    {
        $this->uuid = $uuid;
        $this->type = $type;
        $this->name = $name;
        $this->description = $description;
        $this->default = $default;
    }
    /**
     * @return string
     */
    public function uuid(): string
    {
        return $this->uuid;
    }
    /**
     * @return LocationType
     */
    public function type(): LocationType
    {
        return $this->type;
    }
    /**
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->name;
    }
    /**
     * @return string|null
     */
    public function description(): ?string
    {
        return $this->description;
    }
    /**
     * @return bool|null
     */
    public function isDefault(): ?bool
    {
        return $this->default;
    }
}
