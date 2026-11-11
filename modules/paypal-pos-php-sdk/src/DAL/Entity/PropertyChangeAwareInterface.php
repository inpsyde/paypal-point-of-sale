<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity;

interface PropertyChangeAwareInterface
{
    /**
     * @return bool
     */
    public function hasChangedProperties(): bool;
    /**
     * @return array
     */
    public function allChangedProperties(): array;
}
