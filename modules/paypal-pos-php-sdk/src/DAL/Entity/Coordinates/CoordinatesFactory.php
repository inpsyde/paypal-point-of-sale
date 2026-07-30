<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Coordinates;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Exception\Coordinates\InvalidLatitudeException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Exception\Coordinates\InvalidLongitudeException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Validator\Coordinates\CoordinatesValidator;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\ValidatorException;
class CoordinatesFactory
{
    private CoordinatesValidator $coordinatesValidator;
    /**
     * CoordinatesFactory constructor.
     *
     * @param CoordinatesValidator $coordinatesValidator
     */
    public function __construct(CoordinatesValidator $coordinatesValidator)
    {
        $this->coordinatesValidator = $coordinatesValidator;
    }
    /**
     * @param string $latitude
     * @param string $longitude
     * @param string $accuracyMeters
     *
     * @return Coordinates
     *
     * @throws ValidatorException
     * @throws InvalidLatitudeException
     * @throws InvalidLongitudeException
     */
    public function create(string $latitude, string $longitude, string $accuracyMeters): Coordinates
    {
        $this->coordinatesValidator->validate($latitude, $longitude);
        return new Coordinates((float) $latitude, (float) $longitude, (float) $accuracyMeters);
    }
}
