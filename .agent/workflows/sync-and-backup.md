---
description: Sync with server and create backup
---

# Sync and Backup Workflow

This workflow ensures the local repository is synchronized with the production server and creates a backup.

## Steps

// turbo
1. Execute bidirectional sync with production server
```bash
./tools/sync-with-server.sh
```

// turbo
2. Verify Git status
```bash
git status
```

// turbo
3. Check backup status
```bash
.git/hooks/backup-manager.sh list
```

## Expected Outcome

- Local repository is synchronized with production server (159.198.74.241)
- Fresh backup created in `~/SinbadBackups/`
- Backup log updated
- Old backups rotated if needed
