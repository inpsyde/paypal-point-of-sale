# PayPal Point of Sale

## Installation

The best way to use this package is through Composer:

```BASH
$ composer require inpsyde/paypal-point-of-sale
```

## Requirements

* PHP >= 8.2
* WordPress >= 6.8
* WooCommerce >= 10.2

## Development

0. Install Docker and [DDEV](https://ddev.readthedocs.io/en/stable/).  Edit the configuration in the [`.ddev/config.yml`](.ddev/config.yaml) file if needed.
1. Get your [packagist token](https://packagist.com/orgs/inpsyde),
copy/hardlink your Composer `auth.json` (from `~/.config/composer/auth.json` or `~/.composer/auth.json`)
to `~/.ddev/homeadditions/.composer/auth.json`
2. Run `make setup` to setup DDEV and install dependencies. Go to https://zettle.ddev.site
3. Run `make lint test` to run linter and tests.

Use `make reset` for reinstallation (will destroy all site data).
You may also need `make restart` to apply the config changes.

See [Makefile](/Makefile) for other useful commands.

For Windows users: `make` is not included out-of-the-box but you can simply copy the commands from [Makefile](/Makefile) to `cmd`,
e.g. `ddev exec phpcs`, `ddev exec psalm` instead of `make lint`.

### Webhooks

For testing webhooks locally, follow these steps to set up ngrok:

0. Install [ngrok](https://ngrok.com/).

1. Run our wrapper Bash script which will start `ddev share` and replace the URLs in the WP database:
   ```
   make ngrok
   ```

For other environments, you can instead run `ngrok http -host-header=rewrite zettle.myhost`
and set `NGROK_HOST` env variable to the host that you got from `ngrok`, like `abcd1234.ngrok.io`.
In this case, ngrok will be used only for the webhook listening URL (`zettle.webhook.listener.url` service).
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

to execute tests only in a single module (after `cd` to its' directory inside `modules.local`),
or to execute only integration/acceptance tests in the repository root.

### Linter

Run this to execute PHP_CodeSniffer and psalm checking code style and quality in all modules:

```bash
make lint
```

You can also run

```bash
../../vendor/bin/phpcs .
```

to execute it only in a single module (after `cd` to its' directory inside `modules.local`).

## Crafted by Syde

The team at [Syde](https://syde.com) is engineering the Web since 2006.

## License

Copyright (c) 2020 Moritz Meißelbach, Syde

Good news, this plugin is free for everyone! Since it's released under the [GPL-2.0 License](LICENSE) you can use it free of charge on your personal or commercial website.

## Contributing

All feedback / bug reports / pull requests are welcome.
