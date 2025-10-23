<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity;

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
