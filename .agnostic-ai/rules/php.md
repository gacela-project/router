---
name: router-php
description: PHP conventions for router implementation and examples.
globs: ["src/**", "example/**"]
---

Use declare(strict_types=1), Gacela\Router namespaces under src/Router/, and sibling naming. Let .php-cs-fixer.dist.php define formatting. Keep PHP 8.3 compatibility; Composer's platform override is not a runtime check.

Keep route registration in Configure/, request and response behavior in Entities/, path rules in Validators/, and orchestration in Router. Preserve standalone use when changing Config/RouterGacelaConfig.php; framework integration must stay optional.

For routing changes, consult .agnostic-ai/references/http-contract.md and verify affected behavior. Avoid adding per-request reflection or full route scans without checking existing caches and method indexes. Keep header() calls compatible with the namespaced capture in tests/Feature/header.php.
