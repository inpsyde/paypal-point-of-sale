<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Category;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Category\CategoryCollection;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Category\CategoryCollectionFactory;

class CategoryCollectionBuilder implements CategoryCollectionBuilderInterface
{
    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @var CategoryBuilder
     */
    private $categoryBuilder;

    /**
     * CategoryCollectionBuilder constructor.
     *
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param CategoryBuilderInterface $categoryBuilder
     */
    public function __construct(
        CategoryCollectionFactory $categoryCollectionFactory,
        CategoryBuilderInterface $categoryBuilder
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryBuilder = $categoryBuilder;
    }

    /**
     * @param array $data
     *
     * @return CategoryCollection
     */
    public function buildFromArray(array $data): CategoryCollection
    {
        $categoryCollection = $this->categoryCollectionFactory->create();

        foreach ($data as $category) {
            $categoryCollection->add(
                // TODO We should inspect $data and choose between buildFromArray and buildFromString here
                $this->categoryBuilder->buildFromString($category)
            );
        }

        return $categoryCollection;
    }

    /**
     * @param CategoryCollection $categoryCollection
     *
     * @return array
     */
    public function createDataArray(CategoryCollection $categoryCollection): array
    {
        $data = [];

        foreach ($categoryCollection->all() as $category) {
            $data[] = $this->categoryBuilder->createDataString($category);
        }

        return $data;
    }
}
