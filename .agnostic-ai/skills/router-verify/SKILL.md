---
name: router-verify
description: Select and run checks for a Gacela Router change or diagnose a failing quality gate.
---

Run commands from the repository root. Read composer.json and the diff before choosing checks. Follow .agnostic-ai/AGNOSTIC_AI.md for delivery gates; do not repeat checks that already passed on unchanged code.

| Change | Focused evidence |
| --- | --- |
| Matching, params, groups or named URLs | composer test-phpunit -- --filter '<relevant class or method>'; include request-to-route and URL-to-path cases when both are affected |
| Middleware, output, redirects, HEAD or errors | Relevant feature tests plus run-router request for actual status/headers/body |
| Gacela adapter or bindings | tests/Feature/Config/RouterConfigTest.php and standalone router tests |
| PHP driver helpers | php -l for each changed helper, then canonical driver call and smoke |
| Specs | agnostic-ai validate, agnostic-ai lint, agnostic-ai sync, agnostic-ai sync --check; inspect both targets and referenced paths |

composer test runs formatting, Psalm, PHPStan and PHPUnit sequentially. composer infection requires coverage and enforces 96% MSI floors; use it for mutation-sensitive logic. Neither applies to prose-only changes.

For failures, separate a regression from missing dependencies, unsupported local PHP or coverage configuration. Never change thresholds, suppressions or platform requirements just to turn checks green. Report commands, outcomes and specific untested boundaries.
