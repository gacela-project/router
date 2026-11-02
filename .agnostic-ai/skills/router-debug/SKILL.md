---
name: router-debug
description: Use to diagnose a wrong route match, parameter, middleware order, response or error in Gacela Router.
---

Read `.agnostic-ai/references/http-contract.md` for the affected boundary.

1. Capture method, URI, route registrations, expected status/headers/body and the actual result. Reproduce with the driver in `.agnostic-ai/skills/run-router/SKILL.md`; custom `--routes` files must return a configure Closure.
2. Trace only the failing boundary: Request normalization, Routes indexing/patterns, RouteParams conversion, middleware order, controller creation/invocation, response conversion or handler selection. For surprising matches, check static/dynamic collisions and HEAD fallback.
3. Use `request` for status and headers. An empty 500 body may hide the cause: in scratch routes, temporarily register `Throwable::class` and `Exception::class` handlers that expose it. Never ship them.
4. Add a focused regression beside the nearest test, fix the owning layer, then pick checks with `router-verify`. Report the root cause and the reproduction that now passes.
