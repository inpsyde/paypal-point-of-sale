<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Vat;

interface VatSetterInterface
{
    public function setVat(?Vat $vat): void;
}
