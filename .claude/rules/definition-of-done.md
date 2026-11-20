---
description: When a task is complete
---

# Definition of done

A task is complete when both are true:

1. New or changed behavior has test coverage. Write new tests through the `write-php-unit-test` agent.
2. The relevant tests and lint passed. The developer runs them, or asks you to run them through the `ci` agent. Do not run them unasked; name the suites that must run instead:
   - PHP change: the unit tests of the changed suite (root or module), PHPCS, PHPStan.
   - A change to how the plugin calls the PayPal POS API: name the matching root integration test as optional.

Run the root integration tests only on the developer's explicit go-ahead (see `AGENTS.md`).
