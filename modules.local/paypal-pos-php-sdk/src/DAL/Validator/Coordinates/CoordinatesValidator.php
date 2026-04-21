<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Validator\Coordinates;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Exception\Coordinates\InvalidLatitudeException;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Exception\Coordinates\InvalidLongitudeException;

class CoordinatesValidator
{
    /**
     * @param string $latitude
     * @param string $longitude
     *
     * @return bool
     *
     * @throws InvalidLatitudeException
     * @throws InvalidLongitudeException
     */
    public function validate(string $latitude, string $longitude): bool
    {
        if ($this->validateLatitude($latitude)) {
            throw new InvalidLatitudeException(sprintf('Given %s is not a valid latitude', esc_html($latitude)));
        }

        if ($this->validateLongitude($longitude)) {
            throw new InvalidLongitudeException(sprintf('Given %s is not a valid longitude', esc_html($longitude)));
        }

        return true;
    }

    /**
     * @param string $latitude
     *
     * @return bool
     */
    private function validateLatitude(string $latitude): bool
    {
        return (!$this->isNumericInRange($latitude, -90.0, 90.0));
    }

    /**
     * @param string $longitude
     *
     * @return bool
     */
    private function validateLongitude(string $longitude): bool
    {
        return (!$this->isNumericInRange($longitude, -180.0, 180.0));
    }

    /**
     * @param string $value
     * @param float $min
     * @param float $max
     *
     * @return bool
     */
    private function isNumericInRange(string $value, float $min, float $max): bool
    {
        return !(($value < $min || $value > $max));
    }
}
