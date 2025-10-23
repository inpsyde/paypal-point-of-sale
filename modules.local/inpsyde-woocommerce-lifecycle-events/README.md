# Inpsyde WooCommerce Lifecycle Events

WooCommerce makes it a bit hard to safely listen to arbitrary product changes:
 - Some data (`post_content` and the like) is handled by WP core methods and is saved at different steps
 - Variations are usually entirely handled via `WC_AJAX` and uses some specific hooks to consider
 - This makes saving variable products a multi-step process that spans across multiple requests
 
 Life would be easier if there was a single entrypoint for *any kind of change* made to *any kind of* WooCommerce product.
 This package aims to provide that.

## Installation

Grab the composer package via 
```composer require inpsyde/inpsyde-woocommerce-lifecycle-events```

## Configuration

### As a module
This package implements the [Dhii - Module Interface](https://github.com/Dhii/module-interface).
If your plugin uses modules, require and set it up like the rest of your modules and you're good to go.

### Standalone
If you are not using modules, you can simply instantiate `WcEventsLibrary` and provide your configuration:

```php
$services = []; // Overwrite internals here
$extensions = []; // Listeners go here
$library = new WcEventsLibrary( $services, $extensions );
$library->initialize();
```

## Usage

Event listeners are provided to the module via service extension. Grab the `'inpsyde.wc-lifecycle-events.products'`-entry and register your listeners in the extension like this:
```php
// extensions.php
return [
    'inpsyde.wc-lifecycle-events.products' => function (
        ContainerInterface $container,
        ProductEventListenerRegistry $registry
    ) {
        $registry->onChange(
            function (\WC_Product $new, \WC_Product $old) {
                $foo = 1; // called
            },
            function (\WC_Product_Simple $new, \WC_Product_Simple $old) {
                $bar = 1; // also called!
            },
            function (\WC_Product_Variable $new, \WC_Product_Variable $old) {
                $baz = 1;
            }
        );
        $registry->onPublish(
            function (\WC_Product $product) {
                $success = 'A product was published!';
            }
        );
        $registry->onPropertyChange(
            'stock_quantity',
            function (\WC_Product $new, \WC_Product $old) {
                $oldStock = $old->get_stock_quantity();
                $newStock = $new->get_stock_quantity();
                $success = 'Stock changed!';
            }
        );

        return $registry;
    },

];
```

The Registry provides a set of convenience helpers to listen to specific product changes. All Listeners
receive an instance of `WC_Product` in both old and new state as parameters. 
The second parameter is optional since it is not needed for all event types

 - `onChange(callable ...$callables)` - The most generic entrypoint which fires on every change event.

 
 - `onPropertyChange(string $property, callable ...$callables)` - Calls listeners only if the specified property was changed. 
 This is useful for pretty specific listeners that act on changes to the `stock_quantity` for example.
 
 - `onStatusChange(string $status, callable ...$callables)` - Listeners are called if a product has been transitioned 
 to the specified `$status` (eg. `'publish'`,`'draft'`, etc)
 
 - `onPublish(callable ...$callables)` - Sugar for the above
 - `onTrash(callable ...$callables)` - Sugar for the above
 - `onDraft(callable ...$callables)` - Sugar for the above
 - `onDelete(callable ...$callables)` - Behaves the same as the above, but works differently under the hood
 - `onTypeChange(callable ...$callables)` - Calls listeners if the *type* of product has changed

### Dealing with product types

The event system uses reflection to inspect the parameter types of your listeners
This means that you can specify that you only wish to act on `WC_Product_Variable` types by adding a listener like this:
```php
$registry->onPublish(
    function (\WC_Product_Variable $product) {
        $success = 'A variable product was published!';
    }
);
```

Similarly, you can react to product type transitions by specifying the source and target types in your function parameters:
```php
$registry->onTypeChange(
    function (\WC_Product_External $new, \WC_Product_Download $old) {
        $success = 'A downloadable product was turned into an external product';
    }
);
```
While this example would technically also work with a regular `onChange` listener, using `onTypeChange` adds a guard clause that makes it 
safe to add more loose type-hints that *still* only fire when an actual type transition occurred:

```php
$registry->onTypeChange(
    function (\WC_Product_Variable $new, \WC_Product $old) {
        $success = 'Some product was turned into a variable product';
    }
);
```

### Disabling events

The EventDispatcher can be disabled entirely by calling `Toggle::disable()`. 
This service can be found under `'inpsyde.wc-lifecycle-events.products.toggle'`
This class can be used to completely disable the EventDispatcher.
Use it if you need to silently update products, for example when processing webhooks
in a setup where you sync products to a remote service on every change

## Future ideas
 - **Make the package PSR-14-compatible.** The package very closely follows PSR-14. But this is almost coincidental, 
 since it was just a result getting the initial prototype properly decoupled for testing. Due to the fact that this module 
 was conceived in a `php7.2`-only environment, PSR-14 was considered out of scope for the time. 
 But once the initial release is stable, a `2.0` release could contain a `php7.3` version bump together with full support for PSR-14
 - **Extend the system to other object types.** The most obvious one being support for orders.
 - **Improve performance.** We currently need to check each listener with reflection whenever `getListenersForEvent` is 
 called and then produce the actual listener out of the given callback. This could happen during registration if we group listeners differently:
 The derived types could be used as keys of a multi-dimensional array. Then we just compare the keys with the product types of the current event.

## Crafted by Inpsyde

The team at [Inpsyde](https://inpsyde.com) is engineering the Web since 2006.

## License

Copyright (c) 2020 Moritz Meißelbach, Inpsyde

Good news, this plugin is free for everyone! Since it's released under the [GPL-2.0 License](LICENSE) you can use it free of charge on your personal or commercial website.

## Contributing

All feedback / bug reports / pull requests are welcome.