<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Coordinates;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\BuilderInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Coordinates\Coordinates;

interface CoordinatesBuilderInterface extends BuilderInterface
{
    /**
     * @param array $data
     *
     * @return Coordinates
     */
    public function buildFromArray(array $data): Coordinates;

    /**
     * @param Coordinates $coordinates
     *
     * @return array
     */
    public function createDataArray(Coordinates $coordinates): array;
}
