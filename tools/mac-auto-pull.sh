#!/bin/bash
#
# Auto-pull script for Mac
# Автоматически получает обновления с GitHub
#

REPO_DIR="$HOME/Desktop/sinbad-portal-local"
LOG_FILE="$HOME/Library/Logs/sinbad-portal-auto-pull.log"

# Проверка существования директории
if [ ! -d "$REPO_DIR" ]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: Directory not found: $REPO_DIR" >> "$LOG_FILE"
    exit 1
fi

cd "$REPO_DIR" || exit 1

# Проверка, что это git репозиторий
if [ ! -d ".git" ]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: Not a git repository: $REPO_DIR" >> "$LOG_FILE"
    exit 1
fi

# Fetch изменений
git fetch origin main >> "$LOG_FILE" 2>&1

# Проверка наличия новых коммитов
LOCAL=$(git rev-parse HEAD)
REMOTE=$(git rev-parse origin/main)

if [ "$LOCAL" != "$REMOTE" ]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] New commits detected. Pulling..." >> "$LOG_FILE"
    
    # Сохранить незакоммиченные изменения в stash, если есть
    if ! git diff-index --quiet HEAD --; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Stashing local changes..." >> "$LOG_FILE"
        git stash push -m "Auto-stash before pull $(date '+%Y-%m-%d %H:%M:%S')" >> "$LOG_FILE" 2>&1
        STASHED=true
    else
        STASHED=false
    fi
    
    # Pull с rebase
    git pull --rebase origin main >> "$LOG_FILE" 2>&1
    
    if [ $? -eq 0 ]; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] Successfully pulled changes" >> "$LOG_FILE"
        
        # Восстановить stash, если был
        if [ "$STASHED" = true ]; then
            echo "[$(date '+%Y-%m-%d %H:%M:%S')] Restoring stashed changes..." >> "$LOG_FILE"
            git stash pop >> "$LOG_FILE" 2>&1
        fi
    else
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: Pull failed" >> "$LOG_FILE"
        exit 1
    fi
else
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] No new commits" >> "$LOG_FILE"
fi

exit 0


