<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Builder;

class CallbackBuilder implements BuilderInterface
{
    /**
     * @var callable
     */
    private $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * @inheritDoc
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     */
    public function build(string $className, $payload, ?BuilderInterface $builder = null)
    {
        $result = ($this->callback)($className, $payload, $builder);
        assert($result instanceof $className);

        return $result;
    }
}
