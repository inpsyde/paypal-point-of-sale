=== PayPal Point of Sale for WooCommerce ===
Contributors: zettle, syde, biont, bschaeffner, alexp11223, danielhuesken
Tags: payments, point-of-sale, woocommerce
Requires at least: 6.8
Tested up to: 6.8
Requires PHP: 8.2
Stable tag: 1.6.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

PayPal Point of Sale Integration for WooCommerce

== Description ==

PayPal Point of Sale is a one-stop shop for cutting-edge commerce tools - offering everything you need to take quick payments, ease day-to-day management, and get the funding to grow.

The PayPal Point of Sale system allows you to take cash, card, contactless payments and more. Connect WooCommerce with PayPal Point of Sale to keep products and inventory in sync - all in one place. Keep your products up-to-date by managing them solely in WooCommerce, so you can focus on selling. Make a sale on either platform and your inventory will update instantly.

The PayPal Point of Sale for WooCommerce provides the following benefits:
- Connect in minutes - Connect your accounts, sync your library to PayPal Point of Sale and start selling.
- Export a collection or all of your products from WooCommerce to your PayPal Point of Sale product library.
- Manage products in one place and automatically synchronise all changes you make from WooCommerce to PayPal Point of Sale.
- Automatically synchronise stock amounts in real-time between WooCommerce and PayPal Point of Sale.
- Explicitly select products to be excluded from synchronisation to PayPal Point of Sale.

PayPal Point of Sale is available in the following countries: [US](https://www.paypal.com/business/pos?utm_source=local_partnership&utm_medium=ecommerce&utm_campaign=woocommerce), [GB](https://www.zettle.com/gb/integrations/e-commerce/woocommerce?utm_source=local_partnership&utm_medium=ecommerce&utm_campaign=woocommerce), [FR](https://www.zettle.com/fr/integrations/e-commerce/woocommerce?utm_source=local_partnership&utm_medium=ecommerce&utm_campaign=woocommerce), [SE](https://www.zettle.com/se/integrationer/e-handel/woocommerce?utm_source=local_partnership&utm_medium=ecommerce&utm_campaign=woocommerce), [NO](https://www.zettle.com/no/integrasjoner/e-handel/woocommerce?utm_source=local_partnership&utm_medium=ecommerce&utm_campaign=woocommerce), [FI](https://www.zettle.com/fi/integraatiot/verkkokauppa/woocommerce?utm_source=local_partnership&utm_medium=ecommerce&utm_campaign=woocommerce), [DK](https://www.zettle.com/dk/integrationer/e-commerce/woocommerce?utm_source=local_partnership&utm_medium=ecommerce&utm_campaign=woocommerce), [NL](https://www.zettle.com/nl/koppelingen/webshop/woocommerce?utm_source=local_partnership&utm_medium=ecommerce&utm_campaign=woocommerce), [DE](https://www.zettle.com/de/integrationen/e-commerce/woocommerce?utm_source=local_partnership&utm_medium=ecommerce&utm_campaign=woocommerce), [ES](https://www.zettle.com/es/integraciones/woocommerce?utm_source=local_partnership&utm_medium=ecommerce&utm_campaign=woocommerce), [IT](https://www.zettle.com/it/integrazioni/woocommerce?utm_source=local_partnership&utm_medium=ecommerce&utm_campaign=woocommerce), [BR](https://www.zettle.com/br/integracoes/woocommerce?utm_source=local_partnership&utm_medium=ecommerce&utm_campaign=woocommerce), [MX](https://www.zettle.com/mx/integraciones/woocommerce?utm_source=local_partnership&utm_medium=ecommerce&utm_campaign=woocommerce)

== Upgrade Notice ==

== Installation ==

To install and use the PayPal Point of Sale for WooCommerce you will need:

- An installed version of WordPress >= 5.4
- An installed and activated version of WooCommerce >= 4.3
- PHP version >= 7.4

= Automatic Installation =

This is the easiest way to install the Point of Sale Integration for WooCommerce.
1. Log into your WordPress installation.
2. Go to the menu item *Plugins* and then to *Add new*.
3. Search for *Point of Sale Integration*. In case several plugins are listed, check if *Zettle* is the plugin author.
4. Click *Install Now* and wait until WordPress reports the successful installation.
5. Activate the plugin. You can find the settings here: *WooCommerce => Settings => Point of Sale Integration*.

= Manual Installation =

In case the automatic installation doesn't work, download the plugin from here via the *Download*-button.
Unpack the archive and load the folder via FTP into the directory `wp-content\plugins` of your WordPress installation.
Go to *Plugins => Installed plugins* and click *Activate* on *Point of Sale Integration for WooCommerce*.

== Frequently Asked Questions ==

[You can find a detailed FAQ in the Point of Sale Integration for WooCommerce documentation](https://woocommerce.com/document/paypal-zettle-pos-for-woocommerce/)

= Where do I report security bugs found in this plugin? =

Please report security bugs found in the source code of the PayPal Point of Sale for WooCommerce plugin through the [Patchstack Vulnerability Disclosure Program](https://patchstack.com/database/vdp/88f8897e-5d46-4b3a-b0eb-f67f7fafc16b). The Patchstack team will assist you with verification, CVE assignment, and notify the developers of this plugin.

== Screenshots ==

1. PayPal Point of Sale
2. PayPal Point of Sale installation STEP 1
3. PayPal Point of Sale installation STEP 2
4. PayPal Point of Sale installation STEP 3
5. PayPal Point of Sale installation STEP 4
6. Product sync almost finished
7. WooCommerce is connected to PayPal Point of Sale
8. Exclude product from sync

== Changelog ==
= 1.6.1 =
- Support latest WordPress 6.8 & WooCommerce 9.8.

= 1.6.0 =
- Prevent error when WooCommerce Product meta contains corrupted data.
- WP 6.7 deprecation warnings for translation loading.
- Early Initialization of wptexturize().
- Minimum required PHP version raised to PHP 7.4.
- Support latest WordPress 6.7 & WooCommerce 9.4.

= 1.5.9 =
- Support latest WP, WC.

= 1.5.8 =
- Added "Requires Plugins" header for WooCommerce.
- Fixed missing price when publishing a new product.
- Fixed accessing non-existing database tables on the first activation.
- Fixed PHP 8.2 deprecations.
- Executing shutdown hooks early to improve compatibility.

= 1.5.7 =
- Fixed a link in the onboarding.
- Fixed handling of column name variable type in `manage_posts_custom_column` filter.
- Fixed some random stock sync failures (clearing cache).

= 1.5.6 =
- Migrate to inventory v3 API.
- Fixed database migration query error (in Query Monitor logs etc.).

= 1.5.5 =
- WC High-Performance Order Storage compatibility declaration.
- PHP 8.1 compatibility.
- Allow merchant with WC Shop Manager role to manage the plugin.

= 1.5.4 =
- Use WP HTTP client by default (added paypal-point-of-sale.http.client filter for switching back to the php-http cUrl wrapper).
- Load product statuses in batches, not one per request.
- Do not subscribe to unneeded webhooks.
- Register webhooks at the end of onboarding.

= 1.5.3 =
- Do not send the price unit to avoid its removal.
- Do not rely on variant order in the balance change webhook handler.

= 1.5.2 =
- Fix the package compatibility with PHP 7.2.

= 1.5.1 =
- Optimize auth checks to reduce amount of requests to Zettle.
- Fixed API key change detection when saving settings, so that it takes effect immediately.
- Fixed fatal error on the settings page when auth fails.
- Delete some missing options during resetting/uninstallation (webhooks, integration id, ...).

= 1.5.0 =
- Add plugin status data on the WC Status page.
- Show unhandled errors in CLI mode.
- Add more info to the status on the settings/onboarding page.
- Show admin notice if unsupported PHP version.
- Check required PHP extensions, show notice if not present.
- Disable price sync if currency changed.
- Show a message during onboarding and do not allow price sync if tax rates are not added in WC.
- Support for `dhii/module-interface` 0.3.x.
- Use WP-based image validator.
- Use file extension to determine image type, do not use exif.
- Handle disconnection via JS dialog.
- Format dates using WP settings.
- Do not send variant description.
- Do not expose the API key in the page source code and input fields.
- Show the error about missing tax rate in the status column to improve logs and make it more clear.
- Fix barcode scanning when changing product type and when creating a new product.
- Do not duplicate validation in SDK and sync modules, update/fix validation rules.
- Validate stock quantity to not attempt sync if > 99999.
- Check if can auth before performing actions requiring auth on plugin load and plugin deactivation.
- Improve error message in log when image URL is empty.
- Handle scheduled publishing of products (was not triggering sync).

= 1.4.2 =
- Send `taxExempt` only for sales tax.

= 1.4.1 =
- Add US signup link.

= 1.4.0 =
- Sync barcodes.
- Add barcode input field with ability to scan via camera. Can be overriden via `paypal-point-of-sale.barcode.value`, `paypal-point-of-sale.barcode.standard-input-ui-enabled` filters.
- Send `taxExempt` and `createWithDefaultTax` for new products on sales tax accounts.
- Show warning during onboarding if no default taxes for sales tax.
- Use `taxationType` instead of now redundant `usesVat`.
- Set expiration time for account settings transient.
- Use `createWithDefaultTax` for VAT when no price sync, to simplify VAT handling and fix error when adding a new variation to a variable product.

= 1.3.1 =
- Clear cache (transients) after plugin upgrade.

= 1.3.0 =
* Syncing prices with or without taxes depending on Zettle taxationMode.
* Use "tax" instead of "VAT" in text, to fit all countries.

= 1.2.0 =
* Fix heartbeat filter (Elementor plugin compatibility).
* Support Zettle accounts without VAT.

= 1.1.0 =
* Delete/register WebHooks on plugin deactivation/activation.
* Execute queue on shutdown only if on admin pages to improve performance.
* Use Inpsyde client_id when requesting the API token (for tracking purposes).
* Fix API key validation request during onboarding.
* Skip stock sync if no changes to avoid errors after merging products during onboarding.

= 1.0.0 =
* First release.
