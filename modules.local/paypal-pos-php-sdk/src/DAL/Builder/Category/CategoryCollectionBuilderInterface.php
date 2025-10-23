<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Category;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\BuilderInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Category\CategoryCollection;

interface CategoryCollectionBuilderInterface extends BuilderInterface
{
    /**
     * @param array $data
     *
     * @return CategoryCollection
     */
    public function buildFromArray(array $data): CategoryCollection;

    /**
     * @param CategoryCollection $categoryCollection
     *
     * @return array
     */
    public function createDataArray(CategoryCollection $categoryCollection): array;
}
