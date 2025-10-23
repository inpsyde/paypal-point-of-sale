<?php

declare(strict_types=1);

namespace Inpsyde\WcEvents;

use Inpsyde\WcEvents\Event\ProductEventListenerRegistry;
use Psr\Container\ContainerInterface;
use WC_Product;
use WC_Product_Simple;
use WC_Product_Variable;

return [
    'inpsyde.wc-lifecycle-events.products.listener-provider' => static function (
        ContainerInterface $container,
        ProductEventListenerRegistry $provider
    ): ProductEventListenerRegistry {
        /**
         * The following hooks are temporarily left here for testing and
         * debugging purposes. They should be removed later.
         */
        $provider->onChange(
            static function (WC_Product $new, WC_Product $old) {
                $success = 'Some product has changed!';
            },
            static function (WC_Product_Simple $new, WC_Product_Simple $old) {
                $success = 'A simple product has changed!';
            },
            static function (WC_Product_Variable $new, WC_Product_Variable $old) {
                $oldChildren = $old->get_children();
                $newChildren = $new->get_children();
                $success = 'A variable product has changed!';
            }
        );
        $provider->onPublish(
            static function (WC_Product $new, WC_Product $old) {
                $success = 'A product was published!';
            }
        );

        $provider->onTrash(
            static function (WC_Product $new, WC_Product $old) {
                $success = 'A product was moved to trash';
            }
        );

        $provider->onDraft(
            static function (WC_Product $new, WC_Product $old) {
                $success = 'A product was moved to draft';
            }
        );

        $provider->onDelete(
            static function (WC_Product $new, WC_Product $old) {
                $success = 'A product was deleted';
            }
        );
        $provider->onPropertyChange(
            'description',
            static function (WC_Product $new, WC_Product $old) {
                $oldDesc = $old->get_description();
                $newDesc = $new->get_description();
                $success = 'Description changed!';
            }
        );
        $provider->onPropertyChange(
            'stock_quantity',
            static function (WC_Product $new, WC_Product $old) {
                $oldStock = $old->get_stock_quantity();
                $newStock = $new->get_stock_quantity();
                $success = 'Stock changed!';
            }
        );

        $provider->onTypeChange(
            static function (WC_Product $new) {
                $success = 'Type changed!';
            }
        );

        return $provider;
    },

];
