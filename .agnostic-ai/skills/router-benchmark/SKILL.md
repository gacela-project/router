---
name: router-benchmark
description: Use to measure route matcher performance when changing indexes, regex matching or lookup costs.
---

Read `tests/benchmark-routing.php` and `tests/preg_match.php` before measuring.

```bash
XDEBUG_MODE=off php -d error_reporting='E_ALL & ~E_DEPRECATED' tests/benchmark-routing.php 100 10000
```

The before/after columns compare a linear scan with `findMatching()` in the same checkout, not Git revisions. The regex counter wraps unqualified `preg_match` calls in `Gacela\Router\Entities` only.

To claim a change, run the same harness on current main and the candidate in separate worktrees, sequentially, with equal dependencies. Record both commit IDs, route count, iterations, PHP version and opcache/JIT settings. If the harness itself needs changes, land and baseline that separately first.

Compare static, dynamic, method-bucket and missing-route scenarios. Repeat anomalous timings and report regex counts with elapsed time. Confirm behaviour with routing tests (static/dynamic precedence, HEAD/405) before claiming an improvement. The harness measures matching only, not controller invocation or `RouteParams` reflection caches.
