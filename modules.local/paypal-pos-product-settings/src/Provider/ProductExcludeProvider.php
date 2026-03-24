<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\ProductSettings\Provider;

use Syde\PayPal\PointOfSale\ProductSettings\Handler\ProductExcludeHandler;
use Syde\PayPal\PointOfSale\Provider;
use Psr\Container\ContainerInterface as C;

class ProductExcludeProvider implements Provider
{

    /**
     * @var ProductExcludeHandler
     */
    private $productExcludeHandler;

    /**
     * ProductExcludeProvider constructor.
     *
     * @param ProductExcludeHandler $productExcludeHandler
     */
    public function __construct(ProductExcludeHandler $productExcludeHandler)
    {
        $this->productExcludeHandler = $productExcludeHandler;
    }

    /**
     * @inheritDoc
     */
    public function boot(C $container): bool
    {
        add_action(
            'added_term_relationship',
            function (int $objectId, int $termId, string $taxonomy) {
                if ($this->productExcludeHandler->isExcludable($objectId, $taxonomy, $termId)) {
                    return;
                }

                $this->productExcludeHandler->excludeProduct($objectId);
            },
            10,
            3
        );

        add_action(
            'delete_term_relationships',
            function (int $objectId, array $termIds, string $taxonomy) {
                $termIds = array_map('absint', $termIds);

                if (!$this->productExcludeHandler->isIncludable($objectId, $taxonomy, ...$termIds)) {
                    return;
                }

                $this->productExcludeHandler->includeProduct($objectId);
            },
            10,
            3
        );

        return true;
    }
}
