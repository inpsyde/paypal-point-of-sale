<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Discount;

use DateTime;
use Exception;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Image\ImageCollection;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Price\Price;

final class DiscountFactory
{
    /**
     * @param string $uuid
     * @param string $name
     * @param string $description
     * @param ImageCollection|null $imageCollection
     * @param Price|null $amount
     * @param float|null $percentage
     * @param string|null $externalReference
     * @param string|null $etag
     * @param string|null $updatedAt
     * @param string|null $updatedBy
     * @param string|null $createdAt
     *
     * @return Discount
     *
     * @throws Exception
     */
    public function create(
        string $uuid,
        string $name,
        string $description,
        ?ImageCollection $imageCollection = null,
        ?Price $amount = null,
        ?float $percentage = null,
        ?string $externalReference = null,
        ?string $etag = null,
        ?string $updatedAt = null,
        ?string $updatedBy = null,
        ?string $createdAt = null
    ): Discount {
        return new Discount(
            $uuid,
            $name,
            $description,
            $imageCollection,
            $amount,
            $percentage,
            $externalReference,
            $etag,
            new DateTime($updatedAt),
            $updatedBy,
            new DateTime($createdAt)
        );
    }
}
