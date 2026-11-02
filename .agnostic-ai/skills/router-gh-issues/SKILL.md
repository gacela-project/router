---
name: router-gh-issues
description: Use only when asked to work through a queue of open Gacela Router issues (--limit N, --label LABEL, --dry-run).
disable-model-invocation: true
---

Fetch open issues unassigned or assigned to the current user (paginate, deduplicate), oldest first, and show the queue. `--dry-run` stops here.

Process sequentially with `router-gh-issue`. Recheck assignment before each issue; skip issues assigned to someone else. Keep local work. Stop and report unresolved validation failures or blocked prerequisites.

Leave PRs for review unless merging is authorized; then wait for required CI and clean up before the next issue.
