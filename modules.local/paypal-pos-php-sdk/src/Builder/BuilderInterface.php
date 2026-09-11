<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Builder;

use Syde\PayPal\PointOfSale\PhpSdk\Exception\BuilderException;

interface BuilderInterface
{
    /**
     * @throws BuilderException
     */
    public function build(string $className, mixed $payload, ?BuilderInterface $builder = null): mixed;
}
