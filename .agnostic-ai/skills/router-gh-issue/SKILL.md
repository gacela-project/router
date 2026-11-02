---
name: router-gh-issue
description: Use to fix, work or pick up one Gacela Router GitHub issue by number or URL and open its PR.
---

Use the global `gh-issue` skill only for what this one omits.

1. Read the issue and every comment. Verify findings against current code; state the acceptance scenario and non-goals.
2. Fetch `origin/main` and branch from it: `fix/<number>-<slug>`, `feat/<number>-<slug>` or `docs/<number>-<slug>`. Keep local work; use a worktree if needed. Self-assign if unassigned.
3. Implement the scenario and regression tests. Follow `.agnostic-ai/AGNOSTIC_AI.md` for validation and changelog.
4. Review touched code for duplication, naming drift and unused abstractions. Commit with the repo's conventional style.
5. Open a PR with `Closes #<number>`, acceptance evidence and validation limits. Pass multiline text by body file. Check required CI; fix failures within scope.
6. Merge only if authorized, then remove the task branch and worktree, keeping unpushed work.
