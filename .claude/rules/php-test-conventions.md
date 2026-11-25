---
description: PHP test writing conventions
paths:
  - "tests/PHPUnit/**/*.php"
  - "modules.local/*/tests/**/*.php"
---

Write new PHP tests, helpers, stubs, and fixtures only through the `write-php-unit-test` agent, and give it full context: tests written outside the agent miss its conventions and edge cases. You may make a small edit yourself when a behavior change breaks an existing test: an expected value, or a data provider row. Name the edit in your answer. The agent does not run its tests. Show the test list from its report to the user unchanged.
