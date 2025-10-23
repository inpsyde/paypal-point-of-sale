# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [[*next-version*]]

## 1.6.1
### Changed
- Support latest WordPress 6.8 & WooCommerce 9.8.

## 1.6.0
### Fixed
- Prevent error when WooCommerce Product meta contains corrupted data.
- WP 6.7 deprecation warnings for translation loading.
- Early Initialization of wptexturize().

### Changed
- Minimum required PHP version raised to PHP 7.4.
- Support latest WordPress 6.7 & WooCommerce 9.4.

## 1.5.9
### Added
- Support latest WP, WC.

## 1.5.8
### Added
- "Requires Plugins" header for WooCommerce.

### Fixed
- Missing price when publishing a new product.
- Accessing non-existing database tables on the first activation.
- PHP 8.2 deprecations.

### Changed
- Executing shutdown hooks early to improve compatibility.

## 1.5.7
### Fixed
- Link in the onboarding.
- Handling of column name variable type in `manage_posts_custom_column` filter.
- Some random stock sync failures (clearing cache).

## 1.5.6
### Fixed
- Database migration query error (in Query Monitor logs etc.).

### Changed
- Migrate to inventory v3 API.

## 1.5.5
### Added
- WC High-Performance Order Storage compatibility declaration.

### Fixed
- PHP 8.1 compatibility.

### Changed
- Allow merchant with WC Shop Manager role to manage the plugin.

## 1.5.4
### Changed
- Use WP HTTP client by default (added `paypal-point-of-sale.http.client` filter for switching back to the php-http cUrl wrapper).
- Load product statuses in batches, not one per request.
- Do not subscribe to unneeded webhooks.
- Register webhooks at the end of onboarding.

## 1.5.3
### Fixed
- Do not send the price unit to avoid its removal.
- Do not rely on variant order in the balance change webhook handler.

## 1.5.2
### Fixed
- Fix the package compatibility with PHP 7.2.

## 1.5.1
### Changed
- Optimize auth checks to reduce amount of requests to Zettle.

### Fixed
- Fixed API key change detection when saving settings, so that it takes effect immediately.
- Fixed fatal error on the settings page when auth fails.
- Delete some missing options during resetting/uninstallation (webhooks, integration id, ...).

## 1.5.0
### Added
- Support for `dhii/module-interface` 0.3.x.
- Add plugin status data on the WC Status page.
- Show unhandled errors in CLI mode.
- Add more info to the status on the settings/onboarding page.
- Show admin notice if unsupported PHP version.
- Check required PHP extensions, show notice if not present.
- Disable price sync if currency changed.
- Show a message during onboarding and do not allow price sync if tax rates are not added in WC.

### Changed
- Use WP-based image validator.
- Use file extension to determine image type, do not use exif.
- Handle disconnection via JS dialog.
- Format dates using WP settings.
- Do not send variant description.

### Fixed
- Do not expose the API key in the page source code and input fields.
- Show the error about missing tax rate in the status column to improve logs and make it more clear.
- Fix barcode scanning when changing product type and when creating a new product.
- Do not duplicate validation in SDK and sync modules, update/fix validation rules.
- Validate stock quantity to not attempt sync if > 99999.
- Check if can auth before performing actions requiring auth on plugin load and plugin deactivation.
- Improve error message in log when image URL is empty.
- Handle scheduled publishing of products (was not triggering sync).

### Deprecated
- `dhii/module-interface` 0.2.x.

## 1.4.2
### Fixed
- Send taxExempt only for sales tax.

## 1.4.1
### Added
- Add US signup link.

## 1.4.0
### Added
- Sync barcodes.
- Add barcode input field with ability to scan via camera. Can be overriden via `paypal-point-of-sale.barcode.value`, `paypal-point-of-sale.barcode.standard-input-ui-enabled` filters.
- Send `taxExempt` and `createWithDefaultTax` for new products on sales tax accounts.
- Show warning during onboarding if no default taxes for sales tax.

### Changed
- Use `taxationType` instead of now redundant `usesVat`.
- Set expiration time for account settings transient.

### Fixed
- Use `createWithDefaultTax` for VAT when no price sync, to simplify VAT handling and fix error when adding a new variation to a variable product.

## 1.3.1
### Fixed
- Clear cache (transients) after plugin upgrade.

## 1.3.0
### Added
- Syncing prices with or without taxes depending on PayPal Point of Sale `taxationMode`.

### Changed
- Use "tax" instead of "VAT" in text, to fit all countries.

## 1.2.0
### Added
- Support PayPal Point of Sale accounts without VAT.

### Fixed
- Fix heartbeat filter (Elementor plugin compatibility).

## 1.1.0
### Added
- Delete/register WebHooks on plugin deactivation/activation.

### Changed
- Execute queue on shutdown only if on admin pages to improve performance.
- Use Inpsyde client_id when requesting the API token (for tracking purposes).

### Fixed
- Fix API key validation request during onboarding.
- Skip stock sync if no changes to avoid errors after merging products during onboarding.

## 1.0.0
- First release.
