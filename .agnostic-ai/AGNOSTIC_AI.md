# Gacela Router

Minimal HTTP router for proof-of-concept projects. Keep the API small and standalone use working. Requires PHP >=8.3 and container ^2.1; optional Gacela integration targets ^2.4. Paths are relative to the repository root.

## Code

- `src/Router/Router.php`: matching, middleware, output, errors.
- `Configure/`: routes, bindings, handlers, middleware. `Entities/`: requests, responses, route invocation. `Validators/`: path matching. `UrlGenerator.php`: named URLs.
- `src/Router/Config/RouterGacelaConfig.php`: Gacela integration adapter.
- `tests/Unit/`: values and matching; `tests/Feature/`: router behaviour. `tests/Feature/header.php` captures namespaced `header()` calls.
- `example/example.php` runs the example; `docs/README.md` indexes docs.

## Checks

- Iterating: `composer test-phpunit -- --filter <test>`.
- Before delivering PHP changes: `composer test` (format, Psalm, PHPStan, PHPUnit). Run analyzers sequentially. `composer csfix` formats; `composer csrun` checks.
- Routing or response changes: verify status and headers over real HTTP with `run-router`. Add regressions beside sibling tests.
- Mutation-sensitive changes: `composer infection`; keep the 96% MSI and covered MSI floors.
- CI tests PHP 8.3, 8.4 and 8.5 with locked and highest dependencies.
- User-visible changes update docs and `CHANGELOG.md`.
- Specs only: `agnostic-ai validate`, `agnostic-ai lint`, `agnostic-ai sync --check`.

## Specs

Edit `.agnostic-ai/` and `agnostic-ai.yaml`, then run `agnostic-ai sync`. Generated files are ignored; run sync in each clone.

Skills: `router-gh-issue` (one issue), `router-gh-issues` (queue), `run-router` (runtime checks), `router-debug` (failing request), `router-verify` (choose checks), `router-benchmark` (matcher performance). Behaviour checklist: `.agnostic-ai/references/http-contract.md`.

When delegation is authorized, `router-legolas` investigates one question and `router-aragorn` reviews a diff. Give each a scope and validation budget; run expensive checks in the main session. Both inherit the session model; read-only is an instruction, not a sandbox.
