---
description: Safe refactoring with automatic backup
---

# Safe Refactor Workflow

This workflow ensures safe refactoring with automatic backups before and after changes.

## Steps

// turbo
1. Auto-pull latest changes from remote
```bash
.git/hooks/auto-pull.sh
```

// turbo
2. Create pre-refactor backup
```bash
.git/hooks/backup-manager.sh create
echo "Pre-refactor backup created"
```

3. Perform refactoring operations
   - This step is manual - Antigravity will make the code changes
   - Multiple files may be modified
   - Tests should be run if available

// turbo
4. Verify changes with Git status
```bash
git status
git diff
```

5. Run validation (if tests exist)
   - Execute test suite
   - Verify functionality
   - Check for errors

// turbo
6. Stage and commit changes
```bash
git add .
git commit -m "Refactor: [description of changes]"
```

7. Create post-refactor backup (automatic via post-commit hook)
   - Backup is created automatically after commit
   - No manual action needed

8. Prepare for push
```bash
echo "Changes committed. Ready to push to remote."
echo "Run: git push origin dev"
```

## Expected Outcome

- Pre-refactor backup created
- Refactoring completed and tested
- Changes committed with descriptive message
- Post-refactor backup created automatically
- Ready to push to remote repository

## Rollback Procedure

If refactoring fails:

```bash
# Option 1: Git reset
git reset --hard HEAD~1

# Option 2: Restore from backup
rsync -av ~/SinbadBackups/[pre-refactor-timestamp]/ ~/Desktop/sinbad-portal/
```
