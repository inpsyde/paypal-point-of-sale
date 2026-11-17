# E2E Test Setup & Run Instructions

End-to-end tests use [Playwright](https://playwright.dev/) and run against a local WordPress environment managed by [`@wordpress/env`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) (Docker).

---

## Prerequisites

- Docker Desktop running
- Node.js 22+
- Composer
- `rsync` and `zip` available in your shell (standard on macOS/Linux)

---

## Local Setup

### 1. Install Node dependencies

```bash
npm ci
```

### 2. Copy and fill in environment variables

```bash
cp .env.example.e2e .env
```

Edit `.env` and fill in at minimum:

| Variable | Description |
|----------|-------------|
| `WP_BASE_URL` | WordPress URL — default `http://127.0.0.1:8100` |
| `WP_USERNAME` | WordPress admin username — default `admin` |
| `WP_PASSWORD` | WordPress admin password — default `password` |
| `STORAGE_STATE_PATH` | Auth state directory — default `./storage-states` |
| `STORAGE_STATE_PATH_ADMIN` | Admin auth state file — default `./storage-states/admin.json` |
| `PAYPAL_POS_API_KEY` | PayPal POS sandbox API key (required for upgrade/connect tests only) |

### 3. Start wp-env

```bash
npx wp-env start
```

### 4. Run the full setup script

```bash
npm run e2e:setup
```

This builds a distributable plugin zip (`composer install` + asset build), installs it into wp-env via WP-CLI, and prepares WordPress (pretty permalinks, WooCommerce activation) — the same way CI does it. Tests install from this zip rather than a source bind-mount, so they exercise the real end-user install path.

---

## Running Tests

```bash
# All tests
npm run e2e:test

# Smoke suite only
npm run e2e:smoke

# Critical path suite
npm run e2e:critical

# Regression suite
npm run e2e:regression
```

To run just one test, add `--no-deps` so it skips the full setup chain (see [Test Dependency Model](#test-dependency-model)):

```bash
npx playwright test --grep "POS-581" --no-deps
```

### Test Suite Layout

Tests live under `tests/qa/tests/`, one numbered folder per shard:

| Folder | Covers |
|--------|--------|
| `01-plugin-lifecycle/` | Install, reinstall, activate, deactivate, delete the plugin |
| `02-onboarding/` | Connecting to the PayPal POS sandbox via the onboarding wizard |
| `03-product-sync/` | WooCommerce ↔ POS product sync |
| `04-stock-sync/` | WooCommerce ↔ POS stock/inventory sync |
| `05-webhook/` | Webhook registration with PayPal POS |

Each test's title carries one or more tags (`; smoke;`, `; critical;`, `; regression;`) — that's what `--grep` matches in the `e2e:smoke`/`e2e:critical`/`e2e:regression` scripts above.

---

## PHP Development Workflow (live reload)

By default the plugin source is NOT bind-mounted to the WordPress plugins directory, so PHP file changes require rebuilding the zip and re-running `e2e:setup`.

For faster PHP iteration during development, create a `.wp-env.override.json` at the project root (it is gitignored):

```json
{
  "mappings": {
    "wp-content/plugins/paypal-point-of-sale": ".",
    "plugin-source": "."
  }
}
```

Then restart wp-env:

```bash
npx wp-env start --update
```

PHP changes are now reflected immediately without rebuilding. Remove the override before final test runs so CI conditions are reproduced locally.

---

## Stopping / Resetting the Environment

```bash
# Stop containers (keeps database)
npx wp-env stop

# Full reset — destroys database and reinstalls WordPress
npx wp-env destroy && npx wp-env start && npm run e2e:setup
```

There's also a Playwright-native reset that works against whatever target your `.env` points to (`wpenv` or a remote host like Kinsta):

```bash
npm run e2e:env:reset
```

This resets WordPress + WooCommerce and configures the store for sync testing (permalinks, site visibility, tax settings, WooCommerce REST API keys). It's manual-only — no test suite or CI job triggers it automatically — and it does **not** install the PayPal POS plugin itself, so follow it with `npm run e2e:setup` (local) or the manual install steps in [Remote Environment](#remote-environment-kinsta-tst) (Kinsta).

> ⚠️ Destructive and irreversible — deletes all WordPress files and the database on the target environment. Double-check `WP_BASE_URL`/`WPCLI_ENV_TYPE`/`SSH_HOST` in your `.env` before running this against Kinsta.

### Running everything, in order, on a fresh environment

```bash
npx wp-env start                 # or point .env at Kinsta instead
npm run e2e:setup                # build + install the plugin (wpenv only — see Kinsta section for remote)
npm run e2e:env:reset            # WordPress/WooCommerce reset + store config
npm run e2e:test                 # or e2e:smoke / e2e:critical / e2e:regression
```

---

## Test Dependency Model

A run always follows this order: plugin lifecycle → onboarding → connect to PayPal POS → product sync → stock sync → webhook → disconnect. PayPal POS is connected once and shared by the last three, rather than each reconnecting on its own.

Every test also sets up what it needs by itself (installs the plugin, connects to POS, etc., only if that isn't already done) — that's what makes `--no-deps` safe when you just want to run one test in isolation.

You'll always see one test show as skipped — that's `setup:env`'s destructive reset, which only runs when you explicitly call `npm run e2e:env:reset`.

---

## Remote Environment (Kinsta tst)

To run against `https://stg-tstpaypalpospaypal-ppostest.kinsta.cloud/`, configure `.env` with SSH vars (see `.env.example.e2e` for the full list). Retrieve the host, port, login, and path from the Kinsta dashboard → `tst` environment → SSH/SFTP info.

Optionally start from a clean database with `npm run e2e:env:reset` (see above).

No `wp-env start` or `e2e:setup` needed. Pre-install the plugin on Kinsta first:

```bash
composer install --no-dev --prefer-dist
npm run e2e:build-zip        # or run the rsync+zip block from e2e:setup manually

scp -P <port> tests/qa/resources/files/paypal-point-of-sale.zip <login>@<host>:/tmp/
ssh -p <port> <login>@<host> \
  "wp plugin install woocommerce --activate; \
   wp plugin install /tmp/paypal-point-of-sale.zip --force && \
   wp rewrite structure '/%postname%/' --hard && \
   wp plugin activate woocommerce"
```

WooCommerce is a hard prerequisite for the PayPal POS plugin — install it before installing the zip above (the `;` lets the chain continue even if WooCommerce is already present). Skip it entirely if you already ran `npm run e2e:env:reset`, which installs it for you.

Then run tests normally — the CLI routes over SSH automatically:

```bash
npm run e2e:smoke
```

---

## CI

Tests run automatically via `.github/workflows/e2e-tests.yml`:

| Job | Trigger | Target |
|-----|---------|--------|
| Smoke tests | Every pull request | wp-env (local) |
| Full E2E suite | `workflow_dispatch` → `TARGET=wpenv` | wp-env (local) |
| Kinsta E2E suite | `workflow_dispatch` → `TARGET=kinsta` | Kinsta tst (`stg-tstpaypalpospaypal-ppostest.kinsta.cloud`) |

The wp-env PRE_SCRIPT mirrors `npm run e2e:setup`. The Kinsta job SCPs the zip to Kinsta and pre-installs via SSH before Playwright runs.

**Kinsta CI requires five GitHub secrets** (Settings → Secrets and variables → Actions):

| Secret | Value |
|--------|-------|
| `QA_KINSTA_ENV_FILE` | Full `.env.ci` contents with `WP_BASE_URL=https://stg-tstpaypalpospaypal-ppostest.kinsta.cloud`, `WPCLI_ENV_TYPE=ssh`, and SSH vars |
| `QA_KINSTA_SSH_KEY` | Private SSH key (add the matching public key in Kinsta dashboard → SSH keys) |
| `QA_KINSTA_SSH_HOST` | Kinsta SSH hostname |
| `QA_KINSTA_SSH_PORT` | Kinsta SSH port |
| `QA_KINSTA_SSH_LOGIN` | Kinsta SSH username |
