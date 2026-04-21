<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\ProductSettings\Provider;

use Syde\Vendor\Zettle\Psr\Container\ContainerInterface as C;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\ProductSettings\Handler\ProductExcludeHandler;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Provider;
class ProductExcludeProvider implements Provider
{
    private ProductExcludeHandler $productExcludeHandler;
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
        add_action('added_term_relationship', function (int $objectId, int $termId, string $taxonomy): void {
            if ($this->productExcludeHandler->isExcludable($objectId, $taxonomy, $termId)) {
                return;
            }
            $this->productExcludeHandler->excludeProduct($objectId);
        }, 10, 3);
        add_action('delete_term_relationships', function (int $objectId, array $termIds, string $taxonomy): void {
            $termIds = array_map('absint', $termIds);
            if (!$this->productExcludeHandler->isIncludable($objectId, $taxonomy, ...$termIds)) {
                return;
            }
            $this->productExcludeHandler->includeProduct($objectId);
        }, 10, 3);
        return \true;
    }
}
