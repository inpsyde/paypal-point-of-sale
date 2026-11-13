# [2.1.0](https://github.com/inpsyde/feature-flags/compare/2.0.0...2.1.0) (2026-11-09)


### Features

* add SiteId for a stable targeting ID that can't be traced back to the site ([8a03cfd](https://github.com/inpsyde/feature-flags/commit/8a03cfd830350eaa73bab6c4f20fcc7c40659322))

# [2.0.0](https://github.com/inpsyde/feature-flags/compare/1.1.0...2.0.0) (2026-11-09)


### Bug Fixes

* close the request budget gaps found in review ([5b7e9fb](https://github.com/inpsyde/feature-flags/commit/5b7e9fbab3e4cf68c87e69a5a1f8a6dc6f92fc1a))
* discard the cached flag list when the Unleash SDK version changes ([1cf4ce6](https://github.com/inpsyde/feature-flags/commit/1cf4ce661dab772d53930cfb4ad5c731f99dd452))
* keep siteId and packageVersion when a filter returns its own custom context ([c6b165e](https://github.com/inpsyde/feature-flags/commit/c6b165e69f698582eebf5836c24fdb04a82e9599))
* register the abilities once per namespace and report calls from the abilities hook ([607712c](https://github.com/inpsyde/feature-flags/commit/607712cfa5b5303fc74c0435b9245653895c8394))
* require a targeting ID and guard the context against collisions ([f849975](https://github.com/inpsyde/feature-flags/commit/f8499754fa0b26ffe0889e7910d95a7f986b1f42))
* send the metrics once per interval and after the page ([5a57bf7](https://github.com/inpsyde/feature-flags/commit/5a57bf78259f0f9da0d9c7b60ceea40ac3c341d4))
* tell option names apart reliably and normalise the Unleash URL ([431d409](https://github.com/inpsyde/feature-flags/commit/431d40982c956546d9b888b51307091bfe24ed53))


* feat!: drop the Unleash configuration filters and rename the context filters ([aeb1617](https://github.com/inpsyde/feature-flags/commit/aeb1617b6eb66c6c4a4f70e6f5382b28536e805e))
* feat!: evaluate flags against a minimal, per-site context ([313511d](https://github.com/inpsyde/feature-flags/commit/313511d3887b05d78e05dc73a036b7fdb40f0fab))
* feat!: budget-safe requests and connection status for the Unleash adapter ([3c7926f](https://github.com/inpsyde/feature-flags/commit/3c7926fdfc8cbee7abf56659b90306b382f0651c))
* feat!: make the abilities opt-in, namespaced and capability-gated ([a13a2f1](https://github.com/inpsyde/feature-flags/commit/a13a2f115b2eaa6d696f7357b09d91b6fefa4828))
* refactor!: adopt PHP 8.2 language features ([e2d0742](https://github.com/inpsyde/feature-flags/commit/e2d07421b405fad4678bd6bda84736e9db814350))
* feat!: require PHP 8.2 ([61057b7](https://github.com/inpsyde/feature-flags/commit/61057b7b0ce3b33a3686af5c9e5afe3fa6aae369))
* feat!: stable cache keys, cleanup and purge for the options cache ([dd9acef](https://github.com/inpsyde/feature-flags/commit/dd9acef5ff4d1dcdf5fcc750c5dba1e67bc72035))
* feat!: configure Unleash projects through an immutable builder ([66cd095](https://github.com/inpsyde/feature-flags/commit/66cd0959ad85ad12a16f0110c3ab5d3147fe6b30))
* feat!: require PHP 8.0 and make the Unleash SDK optional ([d85e849](https://github.com/inpsyde/feature-flags/commit/d85e8494b27b582b670f9babccb3b101cffe0e8b))


### Features

* import the Unleash adapter from inpsyde/feature-flags-unleash ([f8619d7](https://github.com/inpsyde/feature-flags/commit/f8619d7ae11ce172dd9c6ba7b0c92bcc83833307))
* write metrics at most every 5 minutes ([7fe7dfb](https://github.com/inpsyde/feature-flags/commit/7fe7dfb94b4f87fcf2d2221ae420f3b121d6be3a))


### BREAKING CHANGES

* The filters inpsyde.feature-flags-unleash.{project}.app-url,
.api-token, .app-name, .instance-id and .package-version are removed;
pass the values to the builder instead. The context filters are renamed
from inpsyde.feature-flags-unleash.context and
inpsyde.feature-flags-unleash.{project}.context to
syde.feature-flags.unleash.context and
syde.feature-flags.unleash.{project}.context.
* The context no longer contains the WordPress user, the
visitor's IP address, the home URL, the server's hostname and IP
address, or wp_get_environment_type(). Strategies and constraints based
on them no longer match. userId is the targeting ID instead of
"{user ID}|{home URL}", and the custom properties instanceId and
instanceIpAddress are removed.
* The Unleash adapter fetches flags every 12 hours ±20%
instead of every 15 seconds, sends metrics once a day, and no longer
registers with Unleash by default. UnleashProject's constructor takes a
ConnectionMonitor and is internal; use UnleashProject::builder().

Refs: POS-686
* The abilities are opt-in: call Abilities::register() to
register them. They require the manage_options capability by default.
FeatureFlags::VENDOR and FeatureFlags::getFlags() are removed.

Refs: POS-684
* All classes are final. LocalFeature's $description no
longer accepts null.

Refs: POS-683
* PHP 8.2 or later is required.

Refs: POS-683
* The OptionsCache constructor requires a connection ID,
see OptionsCache::connectionId(). Existing cache options are replaced,
so flags are fetched once after the update.

Refs: POS-685
* UnleashProject::new() is removed. Use
UnleashProject::builder($project)->withAppUrl($url)->withApiToken($token)->build().

Refs: POS-683
* PHP 8.0 or later is required.

Refs: POS-683

# [1.1.0](https://github.com/inpsyde/feature-flags/compare/1.0.0...1.1.0) (2026-01-29)


### Features

* **FeatureFlags:** Add deployment secrets to release workflow ([e782d9d](https://github.com/inpsyde/feature-flags/commit/e782d9d142d150a6d7ce2e78506b057a1a976f42))

# 1.0.0 (2026-01-29)


### Features

* **FeatureFlags:** Move to reusable workflow for semantic releases ([920f38c](https://github.com/inpsyde/feature-flags/commit/920f38c79656cb8e66ebc8ee999788b46ec9e1bb))
