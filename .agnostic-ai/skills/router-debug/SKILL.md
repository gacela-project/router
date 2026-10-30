---
name: router-debug
description: Diagnose wrong route matches, parameters, middleware, responses or errors in Gacela Router.
---

Run commands from the repository root. Read .agnostic-ai/references/http-contract.md for the affected boundary.

1. Capture method, URI, route registrations, expected status/headers/body and actual result. Reproduce with the canonical driver described in .agnostic-ai/skills/run-router/SKILL.md. Custom --routes files must return a configure Closure.
2. Trace only the failing boundary: Request normalization, Routes indexing/patterns, RouteParams conversion, middleware order, controller creation/invocation, response conversion or exception handler selection. Check static/dynamic collisions and HEAD fallback when matching is surprising.
3. Use request for status and headers. An empty 500 body may hide the cause; in scratch routes, temporarily register Throwable::class and Exception::class handlers that expose the exception. Keep diagnostic handlers out of shipped code.
4. Add a focused regression beside the nearest existing test, fix the owning layer, then use router-verify to select checks. Report the root cause and the reproduction that now passes.
