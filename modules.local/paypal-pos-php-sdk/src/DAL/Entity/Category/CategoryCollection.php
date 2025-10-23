<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Category;

final class CategoryCollection
{
    /**
     * @var Category[]
     */
    private $collection = [];

    /**
     * CategoryCollection constructor.
     *
     * @param array|null $categories
     */
    public function __construct(?array $categories = [])
    {
        foreach ($categories as $category) {
            $this->add($category);
        }
    }

    /**
     * @param Category $category
     *
     * @return CategoryCollection
     */
    public function add(Category $category): self
    {
        $this->collection[$category->uuid()] = $category;

        return $this;
    }

    /**
     * @param Category $category
     *
     * @return CategoryCollection
     */
    public function remove(Category $category): self
    {
        unset($this->collection[$category->uuid()]);

        return $this;
    }

    /**
     * @param string $uuid
     *
     * @return Category
     */
    public function get(string $uuid): Category
    {
        return $this->collection[$uuid];
    }

    /**
     * @return Category[]
     */
    public function all(): array
    {
        return $this->collection;
    }

    /**
     * @return CategoryCollection
     */
    public function reset(): self
    {
        $this->collection = [];

        return $this;
    }

    /**
     * @return array
     */
    public function createDataArray(): array
    {
        $data = [];

        foreach ($this->collection as $category) {
            $data[] = $category->uuid();
        }

        return $data;
    }
}
