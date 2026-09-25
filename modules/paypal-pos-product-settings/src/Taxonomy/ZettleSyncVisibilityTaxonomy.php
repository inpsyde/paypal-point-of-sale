<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\ProductSettings\Taxonomy;

class ZettleSyncVisibilityTaxonomy
{
    private string $key;
    private string $attachedPostType;
    public function __construct(string $key, string $attachedPostType)
    {
        $this->key = $key;
        $this->attachedPostType = $attachedPostType;
    }
    /**
     * @return string
     */
    public function key(): string
    {
        return $this->key;
    }
    /**
     * @return string
     */
    public function attachedPostType(): string
    {
        return $this->attachedPostType;
    }
    public function create(): void
    {
        $labels = ['name' => _x('PayPal Point of Sale Sync Visibilities', 'paypal-point-of-sale'), 'singular_name' => _x('PayPal Point of Sale Sync Visibility', 'paypal-point-of-sale'), 'search_items' => __('Search PayPal Point of Sale Sync Visibilities', 'paypal-point-of-sale'), 'all_items' => __('All PayPal Point of Sale Sync Visibilities', 'paypal-point-of-sale'), 'parent_item' => __('Parent PayPal Point of Sale Sync Visibility', 'paypal-point-of-sale'), 'parent_item_colon' => __('Parent PayPal Point of Sale Sync Visibility:', 'paypal-point-of-sale'), 'edit_item' => __('Edit PayPal Point of Sale Sync Visibility', 'paypal-point-of-sale'), 'update_item' => __('Update PayPal Point of Sale Sync Visibility', 'paypal-point-of-sale'), 'add_new_item' => __('Add New PayPal Point of Sale Sync Visibility', 'paypal-point-of-sale'), 'new_item_name' => __('New PayPal Point of Sale Sync Visibility Name', 'paypal-point-of-sale'), 'menu_name' => __('PayPal Point of Sale Sync Visibilities', 'paypal-point-of-sale')];
        $args = ['hierarchical' => \false, 'labels' => $labels, 'public' => \false, 'show_ui' => \false, 'show_admin_column' => \false, 'show_in_nav_menus' => \false, 'show_tagcloud' => \false, 'show_in_rest' => \false, 'query_var' => \true, 'rewrite' => ['slug' => 'topic']];
        register_taxonomy($this->key(), [$this->attachedPostType()], $args);
        register_taxonomy_for_object_type($this->key(), $this->attachedPostType());
    }
}
