<?php

declare(strict_types=1);

namespace Inpsyde\WcEvents\Hooks;

use Inpsyde\WcEvents\DispatchDecider;
use Inpsyde\WcEvents\Event\EventDispatcher;
use Inpsyde\WcEvents\Event\GenericProductChangeEvent;
use Inpsyde\WcEvents\Event\ProductEventListenerRegistry;
use Inpsyde\WcEvents\Toggle;
use WC_Product;
use WC_Product_Variable;

/**
 * Hooks into WP&WC to detect any product changes.
 * Fires a ProductChangeEvent whenever a change was detected
 */
class ProductHooks
{

    /**
     * @var WC_Product[]
     */
    private $snapshots = [];

    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    /**
     * @var Toggle
     */
    private $toggle;

    /**
     * @var DispatchDecider
     */
    private $decider;

    /**
     * ProductHooks constructor.
     *
     * @param EventDispatcher $dispatcher
     */
    public function __construct(
        EventDispatcher $dispatcher,
        Toggle $toggle,
        DispatchDecider $decider
    ) {
        $this->dispatcher = $dispatcher;
        $this->toggle = $toggle;
        $this->decider = $decider;
    }

    /**
     * Registers our magic beforeSave() voodoo-callback into a range of hooks that
     * might fire before a product update.
     */
    public function register(): void
    {
        /**
         * If a WC_Product is saved via wp-admin, all regular WP post-data
         * like post_title, post_content etc will already be saved and not show up in
         * $product->get_changes(). So that method is of no use.
         * Instead, we save a snapshot of the product before WP or WC are processing it
         * and do our own comparison later
         */
        add_action(
            'pre_post_update',
            $this->createWcProductGuard([$this, 'beforeSave']),
            1,
            2
        );
        /**
         * Since we already need the 'woocommerce_before_delete_product' hook for afterSave(),
         * we need to hook beforeSave() into this WP Core hook. Unfortunately,
         * it's not possible to do this via WC API alone
         */
        add_action(
            'before_delete_post',
            $this->createWcProductGuard([$this, 'beforeSave']),
            1,
            2
        );

        /**
         * Support non-CPT-based DataStores as well as potential WC-methods to update products
         * bypassing WC core hooks.
         */
        add_action(
            'woocommerce_before_product_object_save',
            [$this, 'beforeSave'],
            10,
            2
        );
        add_action(
            'woocommerce_admin_process_variation_object',
            [$this, 'beforeSave'],
            10,
            2
        );

        /**
         * TODO We do not explicitly pick up if a variation is added/deleted through WP core methods
         * (->not via the WC API) Maybe WC already does this for us, though. Needs research
         */
        add_action(
            'woocommerce_before_delete_product_variation',
            $this->createWcProductGuard([$this, 'beforeSave'])
        );

        /**
         * There is a code path that sets a product's stock without ever running through a save function
         * of a WP Post or a WC_Product. This is a bit more tricky to get working.
         * The SQL filter is the only place to grab the product before the write operation
         */
        add_filter(
            'woocommerce_update_product_stock_query',
            $this->createWcProductGuard([$this, 'beforeSave'], 1),
            10,
            4
        );

        /**
         * For scheduled publishing (IZET-394),
         * in this case WP does not fire any other hooks before calling db update.
         */
        add_action(
            'publish_future_post',
            $this->createWcProductGuard([$this, 'beforeSave']),
            1
        );

        /**
         * This is a special hack that is needed to properly fetch variations of trashed
         * variable products. By default, only the 'publish' and 'private' status are taken into account
         * It is a bit risky to register this globally here, so if there are any problems,
         * this should be added to the product-specific listener factory below
         */
        add_filter(
            'woocommerce_variable_children_args',
            static function ($args) {
                if (!is_array($args['post_status'])) {
                    $args['post_status'] = [$args['post_status']];
                }
                $args['post_status'][] = 'trash';

                return $args;
            }
        );
    }

    /**
     * The callback that runs before a product is changed. Runs once for each product.
     * Creates the afterSave listener for this product and registers it to the appropriate hooks.
     * Recurses into the parent product if one is found.
     * Lastly, it circumvents lazy-loading of some attributes via preWarmCaches()
     *
     * @wp-hook pre_post_update
     * @wp-hook woocommerce_before_product_object_save
     * @wp-hook woocommerce_before_delete_product_variation
     *
     * @param WC_Product $old
     */
    public function beforeSave(WC_Product $old): void
    {
        $id = $old->get_id();
        $hookName = current_action();
        /**
         * Allow multiple instances on THE SAME entrypoint. The idea here is that
         * we might be seeing a nested update of the same product here
         * (->multiple db calls for whatever reason).
         * So only bail if the current hook is NOT already in use for the current product
         */
        if (isset($this->snapshots[$id]) && !isset($this->snapshots[$id][$hookName])) {
            /**
             * Hm.. if multiple _new_ products (with an ID=0) are created simultaneously
             * and callbacks become nested, this guard will prevent these "inner callbacks"
             * from triggering events. I don't know how to solve this right now.
             */
            return;
        }
        $old = $this->prepareOldProduct($old);
        $hook = $this->createAfterSaveHook($old);
        $this->registerAfterHooks($hook);

        if ($old instanceof WC_Product_Variable) {
            /**
             * WooCommerce will call wc_deferred_product_sync() for the parent,
             * but that is running too late. So we grab the parent manually.
             */
            $parentId = $old->get_parent_id();
            if ($parentId) {
                $parentProduct = wc_get_product($parentId);
                if ($parentProduct instanceof WC_Product) {
                    $this->beforeSave($parentProduct);
                }
            }
        }

        $this->preWarmCaches($old);
        /** @psalm-suppress UndefinedMethod */
        $this->snapshots[$id][$hookName][] = $hook;
    }

    /**
     * Creates individual listeners for a range of hooks that may or may not
     * fire after a product has been saved. Only one of them will be allowed to execute
     * and listeners will be cleaned up after execution.
     *
     * @param WC_Product $old
     *
     * @return callable
     */
    private function createAfterSaveHook(WC_Product $old): callable
    {
        $innerFunction = function (WC_Product $new, WC_Product $old): void {
            if (doing_action('woocommerce_before_delete_product')) {
                /**
                 * There is no way to tell if a product is currently being deleted other than by
                 * checking outside application state (->doing_action('foo')?)
                 * To supply the ListenerProvider with this missing piece of information,
                 * we inject a little meta flag into the product, so it can later be checked against
                 * @psalm-suppress InvalidArgument
                 */
                $new->update_meta_data(ProductEventListenerRegistry::DELETE_FLAG, true);
            }

            $this->afterSave($new, $old);
        };

        /**
         * @noinspection PhpUnnecessaryLocalVariableInspection
         * @psalm-suppress UndefinedVariable
         */
        $hook = $this->createWcProductGuard(
            function (WC_Product $new) use (&$hook, $innerFunction, $old): void {
                /**
                 * Prevents the $innerFunction from being executed more than once
                 */
                static $called;
                if ($called) {
                    return;
                }
                /**
                 * Compare product IDs and bail if they don't match.
                 * We'll continue IF the old product has no ID yet.
                 * In this case we assume the product has just been created and accept a tiny
                 * risk of mismatching products.
                 */
                if ($old->get_id() !== 0 && $new->get_id() !== $old->get_id()) {
                    return;
                }

                $innerFunction($new, $old);

                /**
                 * wp_update_post() is called right in the middle of the saving process.
                 * If we remove our listener already, it would basically interrupt our
                 * ability to send off change events.
                 * TODO: Would be neat to exclude already dispatched changes somehow
                 * -> Update a clone of $new with wp core properties we already saved?
                 */
                if (current_action() === 'wp_insert_post') {
                    return;
                }
                /**
                 * Remove the hook again so we can be sure events are executed
                 * only once for each product
                 */
                foreach ($this->afterSaveHookNames() as $hookName) {
                    remove_action($hookName, $hook);
                }
                unset($this->snapshots[$old->get_id()]);
                $called = true;
            }
        );

        return $hook;
    }

    /**
     * Returns a list of all relevant hooks that could trigger an "afterSave" event
     *
     * @return array
     */
    private function afterSaveHookNames(): array
    {
        return [
            'woocommerce_after_product_object_save',
            'wp_insert_post',
            'woocommerce_trash_product',
            /**
             * Despite this being the "before_delete" hook, it is actually used for our afterSave()
             * handler, since we DO want the product to still be present
             * so that we can read its WC_Product instance
             */
            'woocommerce_before_delete_product',
            'woocommerce_new_product_variation',
            'woocommerce_delete_product_variation',
            'woocommerce_save_product_variation',
            'publish_product',
        ];
    }

    /**
     * Registers the given callback to a range of hooks that might run AFTER a product has been saved
     *
     * @param callable $callable
     */
    private function registerAfterHooks(callable $callable)
    {
        foreach ($this->afterSaveHookNames() as $hookName) {
            add_action($hookName, $callable);
        }
    }

    /**
     * Create a clone instance of the old product and delete its $changes
     * array so we are guaranteed to receive the values before saving
     *
     * @param WC_Product $product
     *
     * @return WC_Product
     */
    private function prepareOldProduct(WC_Product $product): WC_Product
    {
        $clone = clone $product;
        (function () {
            $this->changes = [];
        })->call($clone);

        return $clone;
    }

    /**
     * @wp-hook wp_insert_post
     * @wp-hook woocommerce_after_product_object_save
     *
     * @param WC_Product $new
     * @param WC_Product $old
     */
    public function afterSave(WC_Product $new, WC_Product $old): void
    {
        if (!$this->toggle->isEnabled()) {
            return;
        }

        $event = new GenericProductChangeEvent($new, $old);

        if (!$this->decider->isEventDispatchable($event)) {
            return;
        }
        $this->dispatcher->dispatch($event);
    }

    /**
     * Returns a function that will ensure its inner $callable is called with a
     * WC_Product instance as its first parameter
     *
     * @param callable(WC_Product):void $callable
     *
     * @param int $argPosition
     * The position of the WC_Product instance|id in the 'outer' callback signature
     *
     * @return callable:void
     */
    private function createWcProductGuard(callable $callable, int $argPosition = 0): callable
    {
        //phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
        return static function () use ($callable, $argPosition) {
            $args = func_get_args();
            $product = $args[$argPosition];
            if (!$product instanceof WC_Product) {
                $product = wc_get_product($product);
            }
            if (!$product) {
                return $args[0];
            }
            $callable($product);

            return $args[0]; // in case this is a wp filter
        };
    }

    /**
     * Triggers a few caches so we are guaranteed
     * to receive the 'old' data later on later calls
     */
    private function preWarmCaches(WC_Product $product): void
    {
        $product->get_children();

        switch (true) {
            case $product instanceof WC_Product_Variable:
                $product->get_variation_attributes();
                $product->get_visible_children();
                break;
            default:
        }
    }
}
