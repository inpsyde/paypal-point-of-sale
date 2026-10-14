<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Builder;

interface TypeSpecificBuilderInterface extends BuilderInterface
{
    public function accepts(mixed $payload): bool;
}
