---
name: router-benchmark
description: Measure route matcher performance when changing indexes, regex matching or lookup costs.
---

Run commands from the repository root. Read tests/benchmark-routing.php and tests/preg_match.php before measuring.

```bash
XDEBUG_MODE=off php -d error_reporting='E_ALL & ~E_DEPRECATED' tests/benchmark-routing.php 100 10000
```

The columns before/after compare a linear scan with findMatching() in the same checkout. They do not compare Git revisions. The regex counter wraps unqualified preg_match calls in Gacela\Router\Entities; it does not count global calls or other namespaces.

For a change claim, run the same harness on current main and the candidate in separate worktrees. Record both commit IDs, route count, iterations, PHP version and opcache/JIT settings. Keep dependency resolution equal and run measurements sequentially. If the harness needs changes, establish that baseline separately before measuring the optimization.

Compare static, dynamic, method-bucket and missing-route scenarios. Repeat anomalous timing results and report regex counts alongside elapsed time. Verify behavior with routing tests, especially static/dynamic precedence and HEAD/405, before claiming an improvement. This harness measures matching only; it does not measure controller invocation or RouteParams reflection caches.
