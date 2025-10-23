<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Variant;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Price\Price;

interface PriceAwareInterface
{
    public function price(): ?Price;

    public function setPrice(?Price $price): void;
}
