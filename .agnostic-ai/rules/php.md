---
name: router-php
description: PHP conventions for router implementation and examples.
globs: ["src/**", "example/**"]
---

Use `declare(strict_types=1)`, `Gacela\Router` namespaces under `src/Router/`, and sibling naming. `.php-cs-fixer.dist.php` defines formatting. Keep PHP 8.3 compatible; Composer's platform override is not a runtime check.

Route registration lives in `Configure/`, request and response behaviour in `Entities/`, path rules in `Validators/`, orchestration in `Router`. Changing `Config/RouterGacelaConfig.php` must keep standalone use working; framework integration stays optional.

For routing changes, check `.agnostic-ai/references/http-contract.md` and verify the affected behaviour. Before adding per-request reflection or a full route scan, check the existing caches and method indexes. Keep `header()` calls compatible with the namespaced capture in `tests/Feature/header.php`.
