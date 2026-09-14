<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Builder;

use Syde\PayPal\PointOfSale\PhpSdk\Exception\BuilderException;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\BuilderNotFoundException;

class TypeDelegatingBuilder implements BuilderInterface
{
    /**
     * @var TypeSpecificBuilderInterface[]
     */
    private array $builders;

    public function __construct(TypeSpecificBuilderInterface ...$builders)
    {
        $this->builders = $builders;
    }

    /**
     * @throws BuilderException
     */
    public function build(string $className, mixed $payload, ?BuilderInterface $builder = null): mixed
    {
        foreach ($this->builders as $typeSpecificBuilder) {
            if (!$typeSpecificBuilder->accepts($payload)) {
                continue;
            }

            return $typeSpecificBuilder->build($className, $payload, $builder ?? $this);
        }
        $type = $this->inferType($payload);
        throw new BuilderNotFoundException("No Builder found for type '" . esc_html($type) . "'");
    }

    private function inferType(mixed $payload): string
    {
        if (is_null($payload)) {
            return 'null';
        }
        if (is_object($payload)) {
            return get_class($payload);
        }
        return 'something';
    }
}
