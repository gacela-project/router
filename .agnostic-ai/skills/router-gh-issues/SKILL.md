---
name: router-gh-issues
description: Work through a queue of open Gacela Router issues.
disable-model-invocation: true
---

Accept --limit N, --label LABEL and --dry-run. Fetch open issues unassigned or assigned to the current user, following pagination. Deduplicate by number, order oldest first and show the queue. Dry-run stops here.

Process sequentially with router-gh-issue. Recheck assignments before each issue; skip issues assigned to someone else. Preserve local work. Stop and report unresolved validation failures or blocked prerequisites.

Leave reviewable PRs unless merging is authorized. When authorized, wait for required CI and finish cleanup before continuing.
