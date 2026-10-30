---
name: router-gh-issue
description: Implement a Gacela Router issue by number or URL and prepare its PR.
---

Paths are relative to the repository root. Use the global gh-issue skill for guidance this workflow does not cover.

1. Read the issue and every comment. Verify findings against current code; state the acceptance scenario and non-goals.
2. Fetch origin/main and branch from it: fix/<number>-<slug>, feat/<number>-<slug>, or docs/<number>-<slug>. Preserve local work; use a worktree when needed. Self-assign if unassigned.
3. Implement the acceptance scenario and regression tests. Follow .agnostic-ai/AGNOSTIC_AI.md for validation and changelog rules.
4. Review touched code for duplication, naming drift and unused abstractions. Commit real changes only, using the repository's conventional commit style.
5. Open a PR with Closes #<number>, acceptance evidence and validation limits. Use a body file for multiline GitHub text. Check required CI and fix failures within scope.
6. Merge when authorized, then clean up the task branch and worktree while preserving unpushed work.
