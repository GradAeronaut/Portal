#!/bin/bash
# Каноничный deploy скрипт для сервера
# Должен быть размещен на сервере и вызываться webhook'ом

set -e

# Путь к репозиторию на сервере
REPO_DIR="/var/www/gradaeronaut.com"

# Логирование
LOG_FILE="/var/log/portal-deploy.log"
exec >> "$LOG_FILE" 2>&1

echo "=== DEPLOY STARTED $(date) ==="

# Переход в директорию репозитория
cd "$REPO_DIR" || {
    echo "ERROR: Cannot cd to $REPO_DIR"
    exit 1
}

# Получение обновлений
echo "Fetching from origin..."
git fetch origin

# Жесткий сброс на origin/main
echo "Resetting to origin/main..."
git reset --hard origin/main

# Опционально: перезагрузка сервисов (раскомментировать при необходимости)
# echo "Reloading services..."
# sudo systemctl reload nginx 2>/dev/null || true
# sudo systemctl restart php8.1-fpm 2>/dev/null || true

echo "=== DEPLOY COMPLETED $(date) ==="
echo "HEAD: $(git rev-parse HEAD)"
echo ""

