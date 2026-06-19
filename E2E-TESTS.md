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

This single command:
1. Runs `composer install` to create `vendor/`
2. Builds a distributable plugin zip at `tests/qa/resources/files/paypal-point-of-sale.zip`
3. Installs the zip into the running wp-env instance via WP-CLI
4. Sets up WordPress (pretty permalinks, WooCommerce activation)

> **Why a zip?** Tests exercise the real end-user installation path — the plugin is installed from a distributable archive, not from a raw source bind-mount. POS-565 uploads this zip through the WordPress admin UI exactly as a user would.

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

PHP changes are now reflected immediately without rebuilding. Remember to remove the override before final test runs so CI conditions are reproduced locally.

---

## Stopping / Resetting the Environment

```bash
# Stop containers (keeps database)
npx wp-env stop

# Full reset — destroys database and reinstalls WordPress
npx wp-env destroy && npx wp-env start && npm run e2e:setup
```

---

## Remote Environment (Kinsta tst)

To run against `https://stg-tstpaypalpospaypal-ppostest.kinsta.cloud/`, configure `.env` with SSH vars (see `.env.example.e2e` for the full list). Retrieve the host, port, login, and path from the Kinsta dashboard → `tst` environment → SSH/SFTP info.

No `wp-env start` or `e2e:setup` needed. Pre-install the plugin on Kinsta first:

```bash
composer install --no-dev --prefer-dist
npm run e2e:build-zip        # or run the rsync+zip block from e2e:setup manually

scp -P <port> tests/qa/resources/files/paypal-point-of-sale.zip <login>@<host>:/tmp/
ssh -p <port> <login>@<host> \
  "wp plugin install /tmp/paypal-point-of-sale.zip --force && \
   wp rewrite structure '/%postname%/' --hard && \
   wp plugin activate woocommerce"
```

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

The wp-env PRE_SCRIPT mirrors `npm run e2e:setup` exactly. The Kinsta job SCPs the zip to Kinsta and pre-installs via SSH before Playwright runs.

**Kinsta CI requires five GitHub secrets** (add via Settings → Secrets and variables → Actions):

| Secret | Value |
|--------|-------|
| `QA_KINSTA_ENV_FILE` | Full `.env.ci` contents with `WP_BASE_URL=https://stg-tstpaypalpospaypal-ppostest.kinsta.cloud`, `WPCLI_ENV_TYPE=ssh`, and SSH vars |
| `QA_KINSTA_SSH_KEY` | Private SSH key (add the matching public key in Kinsta dashboard → SSH keys) |
| `QA_KINSTA_SSH_HOST` | Kinsta SSH hostname |
| `QA_KINSTA_SSH_PORT` | Kinsta SSH port |
| `QA_KINSTA_SSH_LOGIN` | Kinsta SSH username |
