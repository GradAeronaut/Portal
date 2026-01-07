#!/bin/bash

# Watch and Sync Script
# Monitors the project directory for changes and automatically commits/pushes.

PROJECT_DIR="/Users/user/Desktop/sinbad-portal"
cd "$PROJECT_DIR" || exit 1

echo "Starting Auto-Sync Watcher for $PROJECT_DIR"
echo "Press [CTRL+C] to stop."

# Helper function to sync
sync_changes() {
    # Check if there are changes
    if [[ -n $(git status -s) ]]; then
        echo "[$(date)] Changes detected. Syncing..."
        git add -A
        git commit -m "auto"
        git push server main
        echo "[$(date)] Sync complete."
    fi
}

# Loop to monitor changes
# Note: In a robust environment we'd use fswatch or inotifywait.
# Here we use a simple polling loop for compatibility.
while true; do
    sync_changes
    sleep 5
done
