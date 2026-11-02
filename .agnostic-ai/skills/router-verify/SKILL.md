---
name: router-verify
description: Use to choose and run checks for a Gacela Router change, or to diagnose a failing quality gate.
---

Read `composer.json` and the diff first. Follow `.agnostic-ai/AGNOSTIC_AI.md` for delivery gates; do not repeat checks that passed on unchanged code.

| Change | Focused evidence |
| --- | --- |
| Matching, params, groups, named URLs | `composer test-phpunit -- --filter '<class or method>'`; cover request-to-route and URL-to-path when both change |
| Middleware, output, redirects, HEAD, errors | Relevant feature tests plus `run-router request` for real status/headers/body |
| Gacela adapter or bindings | `tests/Feature/Config/RouterConfigTest.php` and standalone router tests |
| PHP driver helpers | `php -l` each changed helper, then driver `call` and `smoke` |
| Specs | `agnostic-ai validate`, `lint`, `sync`, `sync --check`; inspect both targets and referenced paths |

`composer test` runs format, Psalm, PHPStan and PHPUnit sequentially. `composer infection` needs coverage and enforces 96% MSI floors; use it for mutation-sensitive logic. Neither applies to prose-only changes.

On failure, separate a regression from missing dependencies, unsupported local PHP or coverage setup. Never change thresholds, suppressions or platform requirements to pass. Report commands, outcomes and untested boundaries.
