<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Sync\Listener;

use WC_Product;
use WC_Product_Variable;
use WC_Product_Variation;
/**
 * @see ProductEventListenerRegistry::onPropertyChange() with 'managing_stock'
 */
class VariationManageStockListener
{
    private VariableManageStockListener $variableListener;
    public function __construct(VariableManageStockListener $variableListener)
    {
        $this->variableListener = $variableListener;
    }
    /**
     * @param WC_Product_Variation $new
     * @param WC_Product $old
     */
    public function __invoke(WC_Product_Variation $new, WC_Product $old): void
    {
        //TODO: Discuss if this is even wanted here if we're delegating to the parent
        if (!(bool) $new->is_purchasable()) {
            return;
        }
        $parentId = (int) $new->get_parent_id();
        $parentProduct = new WC_Product_Variable($parentId);
        ($this->variableListener)($parentProduct);
    }
}
