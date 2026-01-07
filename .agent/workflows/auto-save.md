---
description: Automatically commit and push changes to server/main
---

# Auto-Save Workflow

This workflow stages all changes, commits them with a generated message (or default), and pushes to the `server` remote on branch `main`.

## Steps

// turbo
1. Stage all changes
```bash
git add .
```

// turbo
2. Commit changes
```bash
# Using a generic timestamped message if no message provided
# Ideally the agent should provide a message when running this, but for fully automatic:
git commit -m "Auto-save: $(date '+%Y-%m-%d %H:%M:%S')" || echo "No changes to commit"
```

// turbo
3. Push to server
```bash
git push server main
```
