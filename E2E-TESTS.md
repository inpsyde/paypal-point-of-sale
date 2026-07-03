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

### Resetting via Playwright (`env.setup.ts`)

There is also a Playwright-native reset that works against whatever target your `.env`
currently points to — `wpenv` **or** a remote host like Kinsta — without switching tools:

```bash
npm run e2e:env:reset
```

This runs `tests/qa/tests/_setup/env.setup.ts` (project `setup:env` in
`playwright.config.ts`). What it does depends on `WPCLI_ENV_TYPE`:

- **`wpenv` (local)** — through the `cli` fixture: `wp db reset --yes` →
  `wp core install` (using `WP_BASE_URL` / `WP_USERNAME` / `WP_PASSWORD`) →
  `wp plugin activate woocommerce`.
- **`ssh` (Kinsta)** — runs DevOps' dedicated `${HOME}/bin/reset-wp.sh --wp-version=<WP_VERSION>
  --wp-type=<WP_TYPE>` over a raw SSH call (see [How can QA reset a test
  environment?](https://inpsyde.atlassian.net/wiki/spaces/ENG/pages/6240338010/WordPress+hosting+FAQs#How-can-QA-reset-a-test-environment%3F)).
  This script reinstalls WordPress, recreates the database, restores the **Kinsta MU
  plugin**, and clears Kinsta/WP caches — a raw `wp core install` does not do any of the
  last three, and the Kinsta MU plugin throws an error if it's still active during
  `wp core install`. `WP_VERSION`/`WP_TYPE` default to `6.6.2`/`single` if unset (see
  `.env.example.e2e`).

  `reset-wp.sh` must already be deployed on the target environment by DevOps — it already
  is on the `tst` environment. If you point this at a different Kinsta environment, request
  it first via a Jira ticket in the [SDO board](https://inpsyde.atlassian.net/jira/software/c/projects/SDO/boards/395)
  under the [SDO-1108](https://inpsyde.atlassian.net/browse/SDO-1108) epic.

  The script normally generates a random admin password on each run, but on `tst` DevOps
  has pinned fixed admin credentials in `~/.wp-cli/config.yml` on the server, so
  `WP_USERNAME`/`WP_PASSWORD` in `.env` keep working across resets. If a reset ever leaves
  storage-state creation (`global-setup.ts`) unable to log in, re-check credentials via
  `cat ~/.wp-cli/config.yml` on the environment.

**It never runs on its own.** `setup:env` is not a `dependency` of any shard or setup
project, and the test itself is gated behind `E2E_CONFIRM_RESET=true` (the npm script sets
this for you). Running `npm run e2e:test`, `e2e:smoke`, `e2e:critical`, `e2e:regression`,
or the CI workflow will **not** trigger it — it only runs via an explicit
`npm run e2e:env:reset` call. This mirrors how reset is wired in sibling QA projects (e.g.
`woocommerce-paypal-payments`): DB reset is always a deliberate, standalone step, never
part of the automatic per-shard dependency chain, and it isn't invoked from CI there either.

This only resets WordPress + WooCommerce — it does **not** install the PayPal POS plugin.
Follow up with `npm run e2e:setup` for `wpenv`, or the manual install steps below for Kinsta.

> ⚠️ Destructive and irreversible — deletes all WordPress files and the database on the
> target environment. Double-check `WP_BASE_URL` / `WPCLI_ENV_TYPE` / `SSH_HOST` in your
> `.env` before running this against Kinsta.

---

## Remote Environment (Kinsta tst)

To run against `https://stg-tstpaypalpospaypal-ppostest.kinsta.cloud/`, configure `.env` with SSH vars (see `.env.example.e2e` for the full list). Retrieve the host, port, login, and path from the Kinsta dashboard → `tst` environment → SSH/SFTP info.

Optionally start from a clean database with `npm run e2e:env:reset` (see above) — this
wipes and reinstalls WordPress + WooCommerce on Kinsta, giving you a known-clean state
before installing the plugin.

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

WooCommerce is a hard prerequisite for the PayPal POS plugin — install it before installing
the zip above. `wp plugin install woocommerce --activate` errors harmlessly if it's already
present (the `;` lets the chain continue either way); skip it entirely if you already ran
`npm run e2e:env:reset`, which installs it for you.

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
