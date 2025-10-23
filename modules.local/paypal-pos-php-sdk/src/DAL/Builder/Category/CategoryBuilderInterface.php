<?php

# -*- coding: utf-8 -*-
declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Category;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\BuilderInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Category\Category;

interface CategoryBuilderInterface extends BuilderInterface
{
    /**
     * @param array $data
     *
     * @return Category
     */
    public function buildFromArray(array $data): Category;

    /**
     * @param Category $category
     *
     * @return array
     */
    public function createDataArray(Category $category): array;

    /**
     * @param string $categoryName
     *
     * @return Category
     */
    public function buildFromString(string $categoryName): Category;

    /**
     * @param Category $category
     *
     * @return string
     */
    public function createDataString(Category $category): string;
}
