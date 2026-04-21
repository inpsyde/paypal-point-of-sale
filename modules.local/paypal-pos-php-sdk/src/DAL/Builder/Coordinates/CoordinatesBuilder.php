<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Coordinates;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Coordinates\Coordinates;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Coordinates\CoordinatesFactory;

final class CoordinatesBuilder implements CoordinatesBuilderInterface
{
    private CoordinatesFactory $coordinatesFactory;

    /**
     * CoordinatesBuilder constructor.
     *
     * @param CoordinatesFactory $coordinatesFactory
     */
    public function __construct(CoordinatesFactory $coordinatesFactory)
    {
        $this->coordinatesFactory = $coordinatesFactory;
    }

    /**
     * @inheritDoc
     */
    public function buildFromArray(array $data): Coordinates
    {
        return $this->build($data);
    }

    /**
     * @inheritDoc
     */
    public function createDataArray(Coordinates $coordinates): array
    {
        return [
            'latitude' => $coordinates->latitude(),
            'longitude' => $coordinates->longitude(),
            'accuracyMeters' => $coordinates->accuracyMeters(),
        ];
    }

    /**
     * @param array $data
     *
     * @return Coordinates
     */
    private function build(array $data): Coordinates
    {
        return $this->coordinatesFactory->create(
            $data['latitude'],
            $data['longitude'],
            $data['accuracyMeters']
        );
    }
}
