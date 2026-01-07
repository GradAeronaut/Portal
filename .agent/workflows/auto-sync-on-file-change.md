---
description: Automatically sync changes on any file change (including manual)
---

# Auto Sync on File Change

This workflow describes the mechanism to automatically sync changes including manual edits.
Since the Agent only runs when triggered, "real-time" monitoring of manual edits requires a running background process.

## Steps

1. Run the watcher script
```bash
./tools/watch-and-sync.sh
```

## Manual Trigger equivalent
If the watcher is not running, this workflow is identical to `auto-sync-after-every-task`:
```bash
git add -A
git commit -m "auto"
git push server main
```
