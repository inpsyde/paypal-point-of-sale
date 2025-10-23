<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Coordinates;

final class Coordinates
{
    /**
     * @var float
     */
    private $latitude;

    /**
     * @var float
     */
    private $longitude;

    /**
     * @var float
     */
    private $accuracyMeters;

    /**
     * Coordinates constructor.
     *
     * @param float $latitude
     * @param float $longitude
     * @param float $accuracyMeters
     */
    public function __construct(float $latitude, float $longitude, float $accuracyMeters)
    {
        $this->longitude = $longitude;
        $this->latitude = $latitude;
        $this->accuracyMeters = $accuracyMeters;
    }

    /**
     * @return float
     */
    public function latitude(): float
    {
        return $this->latitude;
    }

    /**
     * @return float
     */
    public function longitude(): float
    {
        return $this->longitude;
    }

    /**
     * @return float
     */
    public function accuracyMeters(): float
    {
        return $this->accuracyMeters;
    }
}
