<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Builder;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\BuilderException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\BuilderNotFoundException;
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
     * @inheritDoc
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
     * phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     * @throws BuilderException
     */
    public function build(string $className, $payload, ?BuilderInterface $builder = null)
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
    private function inferType($payload): string
    {
        if (is_null($payload)) {
            return 'null';
        }
        $className = get_class($payload);
        /**
         * @psalm-suppress RedundantCondition
         */
        if ($className) {
            return $className;
        }
        return 'something';
    }
}
