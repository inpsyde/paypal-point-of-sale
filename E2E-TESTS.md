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

### Store preparation (`woocommerce.setup.ts`)

`npm run e2e:env:reset` actually targets the `setup:woocommerce` project, which depends on
`setup:env` — so one command runs the reset above *and* prepares the store for sync
testing:

1. Refresh admin session (the reset invalidated `storage-states/admin.json`)
2. Permalinks (`/%postname%/`)
3. Site visibility → `live` (a fresh install defaults to "Coming soon", which hides prices)
4. WooCommerce REST API keys — generated once and written to `.env` (`WC_API_KEY`/`WC_API_SECRET`)
5. Disable transactional e-mails (no real e-mails sent to test customers)
6. General settings — country/currency from `WC_DEFAULT_COUNTRY` (see `.env.example.e2e`)
7. Tax settings — a UK Standard Rate (20%), matching one of the VAT rates PayPal POS actually
   accepts for a GB-registered sandbox account (`20`/`12.5`/`5`/`4`/`0` — anything else is
   rejected with `VAT_NOT_ALLOWED_IN_COUNTRY`). The library's generic "worldwide 10%" fixture
   used here previously was never a valid UK rate; see `woocommerce.helper.ts`.

Steps 3–7 matter specifically because the WC ↔ POS sync compares prices/totals on both
sides — mismatched country/currency/tax config between WooCommerce and the PayPal POS
sandbox account makes sync assertions meaningless. `setup:woocommerce` is not destructive
on its own, but it only runs together with `setup:env` (same `E2E_CONFIRM_RESET` gate).

---

## Test Dependency Model — What Needs What

Two independent mechanisms decide what's in place before a test runs, and mixing them up is
the easiest way to get a confusing failure. Know which one covers what:

### Automatic (Playwright project `dependencies` — runs for you)

```
setup:paypal-pos  (connects to the real PayPal POS sandbox account)
  ├─ dependencies: shard:plugin-lifecycle (all its tests)
  ├─ dependencies: shard:onboarding (all its tests)
  └─ teardown: teardown:paypal-pos (disconnects, resets onboarding state)

shard:product-sync  ── dependencies ──▶ setup:paypal-pos
shard:stock-sync    ── dependencies ──▶ setup:paypal-pos
```

Playwright project dependencies are transitive. Running **any single test** inside
`03-product-sync/` or `04-stock-sync/` — even by exact file path, even a single `--grep` —
still runs the *entire* `shard:plugin-lifecycle` and `shard:onboarding` suites first, then
`setup:paypal-pos`, then your test, then `teardown:paypal-pos`. There is currently no way to
opt out of this from the CLI; it would require removing those `dependencies` entries in
`playwright.config.ts` (a deliberately deferred follow-up — see below).

Independently of that project graph, `product-sync.spec.ts` and `stock-sync.spec.ts` also
call `ensurePluginState()` + `ensurePosConnected()` in their own `beforeEach`. These are
idempotent: they check "is the plugin already installed/active?" / "does the settings page
already show a connected state?" and only do the expensive install/connect work if not. This
means those two files *could* run standalone (without the `setup:paypal-pos` dependency)
once that dependency is removed — they don't currently rely on the project chain to bootstrap
correctly, they just tolerate it being there too.

### Manual (nothing runs this for you)

`setup:env` and `setup:woocommerce` — the WordPress/WooCommerce reset **and** all store
configuration (site visibility, country/currency, tax rates; see the section above) — are
**never** a `dependency` of any shard, and no CI script triggers them. The only way they run
is an explicit:

```bash
npm run e2e:env:reset
```

### The gap this creates

A completely fresh environment (wp-env just started, or Kinsta just reset by someone else,
plugin never installed) will:

- ✅ auto-install and activate the plugin (`ensurePluginState`)
- ✅ auto-connect to PayPal POS (`ensurePosConnected` / `setup:paypal-pos`)
- ❌ **not** auto-configure the store — it stays in WooCommerce's factory-default state
  (likely "Coming soon" mode, default country/currency, no tax rates)

Product/stock sync tests compare prices and VAT handling between WooCommerce and the PayPal
POS sandbox, so an unconfigured store makes those comparisons meaningless at best, and
reproduces exactly the `VAT_NOT_ALLOWED_IN_COUNTRY` failures fixed in `1179b58` at worst.
**Always run `npm run e2e:env:reset` once against a fresh environment before running anything
beyond the smoke suite.** Closing this gap for good would mean adding a third idempotent
check (`ensureStoreConfigured()` or similar) alongside the two above — not yet done.

### Running everything, in order, on a fresh environment

```bash
npx wp-env start                 # or point .env at Kinsta instead
npm run e2e:setup                # build + install the plugin (wpenv only — see Kinsta section for remote)
npm run e2e:env:reset            # WordPress/WooCommerce reset + store config (country, tax, visibility)
npm run e2e:test                 # or e2e:smoke / e2e:critical / e2e:regression
```

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
