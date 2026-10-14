<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Filter;

// phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
// phpcs:disable Syde.Functions.ReturnTypeDeclaration.NoReturnType
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

    public function accepts(mixed $entity, mixed $payload): bool
    {
        foreach ($this->filters as $filter) {
            if ($filter->accepts($entity, $payload)) {
                return true;
            }
        }

        return false;
    }

    public function filter(mixed $entity, mixed $payload): mixed
    {
        foreach ($this->filters as $filter) {
            if ($filter->accepts($entity, $payload)) {
                $entity = $filter->filter($entity, $payload);
            }
        }

        return $entity;
    }
}
