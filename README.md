# PayPal POS for WooCommerce

Connect your WooCommerce store to PayPal POS (Point of Sale) and keep your products and stock in sync across both. Manage your catalog in WooCommerce, push it to your PayPal POS product library, and let stock levels stay accurate on both sides as sales come in.

## Installation

The best way to use this package is through Composer:

```bash
$ composer require inpsyde/paypal-point-of-sale
```

## Requirements

* PHP >= 8.2
* WordPress >= 6.8
* WooCommerce >= 10.2

## Development

0. Install Docker and [DDEV](https://ddev.readthedocs.io/en/stable/). Edit the configuration in the [`.ddev/config.yml`](.ddev/config.yaml) file if needed.
1. Get your [packagist token](https://packagist.com/orgs/inpsyde),
copy/hardlink your Composer `auth.json` (from `~/.config/composer/auth.json` or `~/.composer/auth.json`)
to `~/.ddev/homeadditions/.composer/auth.json`
2. Run `make setup` to set up DDEV and install dependencies. Go to https://paypal-point-of-sale.ddev.site
3. Run `make lint test` to run the linter and tests.

Use `make reset` for reinstallation (will destroy all site data).
You may also need `make restart` to apply the config changes.

See [Makefile](/Makefile) for other useful commands.

For Windows users: `make` is not included out-of-the-box, but you can simply copy the commands from [Makefile](/Makefile) to `cmd`,
e.g. `ddev exec phpcs`, `ddev exec phpstan analyze` instead of `make lint`.

### Webhooks

For testing webhooks locally, follow these steps to set up ngrok:

0. Install [ngrok](https://ngrok.com/).

1. Run our wrapper Bash script, which will start `ddev share` and replace the URLs in the WP database:
   ```
   make ngrok
   ```

For other environments, you can instead run `ngrok http -host-header=rewrite paypal-point-of-sale.myhost`
and set the `NGROK_HOST` env variable to the host that you got from `ngrok`, like `abcd1234.ngrok.io`.
In this case, ngrok will be used only for the webhook listening URL (`paypal-pos.webhook.listener.url` service).
The URLs displayed on the WordPress pages, used in redirects, etc. will still remain local.

### Tests

Set the API key in `.env.phpunit` and run this to execute all tests in all modules:

```text
make test
```

You can also run

```bash
vendor/bin/phpunit
```

to execute tests only in a single module (after `cd` to its directory inside `modules.local`),
or to execute only integration/acceptance tests in the repository root.

### Linter

Run this to execute PHP_CodeSniffer and PHPStan, checking code style and quality in all modules:

```bash
make lint
```

You can also run

```bash
../../vendor/bin/phpcs .
```

to execute it only in a single module (after `cd` to its directory inside `modules.local`).

### Feature flags

Features can be gated behind flags in [Unleash](https://www.getunleash.io/), through
[`inpsyde/feature-flags`](https://github.com/inpsyde/feature-flags). `bootstrap.php` connects the plugin to the
PayPal POS project in Unleash before it loads the modules, so a flag can decide whether a module is active at all.
Flags are evaluated on the site; Unleash only receives the requests for the flag configuration, daily usage counts and
the project name as instance ID.

Check a flag from a module through the `paypal-pos.feature-flags` service. It works anywhere, also in service
definitions:

```php
$flags = $container->get('paypal-pos.feature-flags');

if ($flags->isOn('my-flag')) {
    // ...
}
```

The second argument of `isOn()` is the default, `false` unless given. It is returned for flags that don't exist
in Unleash, when the plugin has no connection to Unleash, and when Unleash can't be reached and no earlier response is
cached. Choose the default that is safe for merchants. Snippets and support can also check a flag through the filter
`syde.feature-flags.paypal-pos`, and override it with a callback at a priority above 10, after the one that asks
Unleash. An override that should decide which modules are loaded must be added before `plugins_loaded`, e.g. in a
must-use plugin.

To make a whole module active only while a flag is on, wrap it in a `FlaggedModule` in `modules.php`:

```php
return static fn (Flags $flags): array => [
    // ...
    new FlaggedModule($flags, 'order-sync', new OrderSyncModule()),
];
```

While the flag is off, the module adds no services, factories or extensions and its `run()` doesn't run, so nothing
else may depend on its services. Modularity still lists it as added and executed under the module's ID. When the flag
turns off, the module's hooks are gone from the next request on: queue jobs it scheduled fail as unknown job types, and
webhook events only it handles are ignored. Its deactivation hooks run only while the flag is on. Uninstalling
doesn't activate it either, so its options in the clean-up lists (`paypal-pos.onboarding.resettable.*`) stay behind on
sites where the flag was on and later turned off. Activating it would run code that may be off for a reason, and an
error there would stop the clean-up of every module.

Flags are evaluated against a context that describes the site, not the visitor:

* `siteId` (also used as `userId`): an HMAC of the home URL from the settings without its scheme, keyed with
  `wp_salt('feature_flags')`, so it can't be traced back to the shop. It is the same for every request, also behind
  language domains, and changes when the home URL's domain or path changes or the salts are regenerated.
* `packageVersion`: the plugin version, `0.0.0-dev` in development builds.
* `qaSite`: `true` on QA sites, see below.

The flag configuration is fetched about twice a day and cached in `wp_options`, so a flag change takes up to about
14 hours to reach all sites.

These settings override the connection, e.g. for QA and staging sites or local development. Set them as constants
in `wp-config.php` or as environment variables; a constant wins over an environment variable of the same name, and
an empty value counts as not set. The URL and token are overridden together: with only one of them set, flag checks
return their default and the error is logged once a day.

* `PAYPAL_POS_FEATURE_FLAGS_URL`: the Unleash API URL.
* `PAYPAL_POS_FEATURE_FLAGS_TOKEN`: a client API token. It selects the Unleash environment.
* `PAYPAL_POS_FEATURE_FLAGS_QA_SITE`: `true` marks the site as a QA site (`qaSite` in the context).

To point the DDEV site at the `development` environment, add the URL and a client token of that environment (from
the team vault) to `.ddev/config.local.yaml`, which is not committed, and run `ddev restart`:

```yaml
web_environment:
  - PAYPAL_POS_FEATURE_FLAGS_URL=https://<unleash-host>/api
  - PAYPAL_POS_FEATURE_FLAGS_TOKEN=<development client token>
```

The cache is tied to the URL and token, so changing them fetches the flags again.

To test how the plugin behaves when Unleash can't be reached, set the URL to a host that doesn't answer, e.g.
`https://unleash.invalid/api`, with any token. The cache belongs to the previous URL, so flag checks return their
defaults.

### Publishing to WordPress.org

Releases are published via the [`Publish to WordPress.org`](.github/workflows/release.yml) GitHub Action, which wraps
[`inpsyde/reusable-workflows`' `wordpress-org-release.yml`](https://github.com/inpsyde/reusable-workflows/blob/main/docs/wordpress-org-release.md).

Trigger it manually (`workflow_dispatch`) with:

* **`PLUGIN_VERSION`** — the version to publish, as `MAJOR.MINOR.PATCH`. Must match both the `Version:` header in
  `zettle-pos-integration.php` and the `Stable tag` in `readme.txt`.
* **`GIT_REF`** — the build branch or tag to publish, e.g. `main`. This must be a *build* branch (created by the
  [`Build`](.github/workflows/build.yml) workflow, `dev/main` → `main`), never a `dev/*` source branch — those still
  carry a `{VERSION}` placeholder instead of a real version number, and the workflow will refuse to run against one.
* **`UPDATE_TRUNK_ONLY`** — when `true` (default), only SVN trunk is updated; set to `false` to also cut a new SVN tag.

## Crafted by Syde

The team at [Syde](https://syde.com) is engineering the Web since 2006.

## License

Copyright (c) 2025 Syde

Good news, this plugin is free for everyone! Since it's released under the [GPL-2.0 License](LICENSE) you can use it free of charge on your personal or commercial website.

## Contributing

All feedback / bug reports / pull requests are welcome.
