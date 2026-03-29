<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Filter;

// phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
// phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
class CompoundFilter implements FilterInterface
{
    /**
     * @var FilterInterface[]
     */
    private array $filters;

    public function __construct(FilterInterface ...$filters)
    {
        $this->filters = $filters;
    }

    /**
     * @inheritDoc
     */
    public function accepts($entity, $payload): bool
    {
        foreach ($this->filters as $filter) {
            if ($filter->accepts($entity, $payload)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function filter($entity, $payload)
    {
        foreach ($this->filters as $filter) {
            if ($filter->accepts($entity, $payload)) {
                $entity = $filter->filter($entity, $payload);
            }
        }

        return $entity;
    }
}
