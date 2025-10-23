<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Category;

use Exception;
use WC_Product_Variable;
use WP_Term;

class CategoryCollectionFactory
{
    /**
     * @var CategoryFactory
     */
    private $categoryFactory;

    public function __construct(CategoryFactory $categoryFactory)
    {
        $this->categoryFactory = $categoryFactory;
    }

    /**
     * @return CategoryCollection
     */
    public function create(): CategoryCollection
    {
        return new CategoryCollection();
    }

    /**
     * @param WC_Product_Variable $wcProductVariable
     *
     * @return CategoryCollection
     *
     * @throws Exception
     */
    public function createFromWcProductVariable(
        WC_Product_Variable $wcProductVariable
    ): CategoryCollection {
        $categoryCollection = $this->create();

        $terms = wp_get_post_terms(
            $wcProductVariable->get_id(),
            'product_cat'
        );

        if (count($terms) === 0) {
            return $categoryCollection;
        }

        /** @var WP_Term $term */
        foreach ($terms as $term) {
            $categoryCollection->add(
                $this->categoryFactory->create($term->name)
            );
        }

        return $categoryCollection;
    }
}
