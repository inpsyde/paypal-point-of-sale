<?php

namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Builder;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\BuilderException;
interface BuilderInterface
{
    /**
     * @param string $className
     * @param mixed $payload
     * @param BuilderInterface|null $builder
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     * @return mixed
     * @throws BuilderException
     */
    public function build(string $className, $payload, ?BuilderInterface $builder = null);
}
