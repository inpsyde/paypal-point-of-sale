<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\VariantOption;

class VariantOptionDefinitions
{
    /**
     * @var VariantOptionCollection[]
     */
    private $definitions = [];
    public function __construct()
    {
    }
    /**
     * Add new Definition
     *
     * @param string $name
     * @param VariantOptionCollection $definition
     *
     * @return $this
     */
    public function addDefinition(string $name, VariantOptionCollection $definition): self
    {
        $this->definitions[$name] = $definition;
        return $this;
    }
    /**
     * Append new VariantOptions to Definition
     *
     * @param string $name
     * @param VariantOptionCollection $collection
     *
     * @return $this
     */
    public function addCollectionToDefinition(string $name, VariantOptionCollection $collection): self
    {
        if (!array_key_exists($name, $this->definitions())) {
            return $this->addDefinition($name, $collection);
        }
        $this->definitions[$name] = new VariantOptionCollection(...$this->definitions[$name]->all(), ...$collection->all());
        return $this;
    }
    /**
     * @return VariantOptionCollection[]
     */
    public function definitions(): array
    {
        return $this->definitions;
    }
}
