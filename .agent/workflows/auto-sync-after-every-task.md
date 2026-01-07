---
description: Auto-sync everything to server main (git add -A, commit "auto", push)
---

# Auto Sync After Every Task

This workflow MUST be run after completing any task to ensure changes are deployed to the server.

## Steps

// turbo
1. Add all changes (including deletions)
```bash
git add -A
```

// turbo
2. Commit changes
```bash
git commit -m "auto" || echo "Nothing to commit"
```

// turbo
3. Push to server
```bash
git push server main
```
