# Gacela Router

Minimal HTTP router for proof-of-concept projects. Keep the API small and standalone use working. Requires PHP >=8.3 and container ^2.1; optional Gacela integration targets ^2.4. Paths below are relative to the repository root.

## Code

- src/Router/Router.php coordinates matching, middleware, output and errors.
- Configure/ registers routes, bindings, handlers and middleware; Entities/ holds requests, responses and route invocation; Validators/ matches paths. UrlGenerator.php generates named URLs.
- src/Router/Config/RouterGacelaConfig.php adapts Gacela integration.
- tests/Unit/ covers individual values and matching; tests/Feature/ exercises the router. tests/Feature/header.php captures namespaced header calls.
- example/example.php runs the example; docs/README.md indexes documentation.

## Checks

During iteration, use composer test-phpunit -- --filter <test>. Before delivering PHP changes, run composer test: formatting, Psalm, PHPStan and PHPUnit. Run analyzers sequentially. composer csfix formats; composer csrun checks formatting.

For routing or response changes, use run-router and real HTTP to verify status and headers. Add regressions beside sibling tests. CI tests PHP 8.3, 8.4 and 8.5 with locked and highest dependencies. For mutation-sensitive changes, run composer infection; keep the 96% MSI and covered MSI floors.

Update docs and CHANGELOG.md for user-visible changes. Specs-only checks: agnostic-ai validate, agnostic-ai lint and agnostic-ai sync --check.

## Specs

Edit .agnostic-ai/ and agnostic-ai.yaml, then run agnostic-ai sync. Generated Claude and Codex files are ignored; run sync in each clone. Use router-gh-issue for one issue, router-gh-issues for a queue, and run-router for runtime checks.
