#!/bin/bash

# Простая проверка версий через Git хэши
# Работает локально и сравнивает с origin

set -e

LOCAL_REPO="/Users/user/Projects/sinbad26/portal26"

echo "=== ПРОВЕРКА ИДЕНТИЧНОСТИ ВЕРСИЙ ==="
echo ""

cd "$LOCAL_REPO"

# Локальный HEAD
LOCAL_HEAD=$(git rev-parse HEAD)
echo "Локально:"
echo "  HEAD: $LOCAL_HEAD"
echo "  Коммит: $(git log -1 --oneline HEAD)"
echo ""

# Origin HEAD
ORIGIN_HEAD=$(git rev-parse origin/main 2>/dev/null || echo "")
if [ -z "$ORIGIN_HEAD" ]; then
    echo "⚠ Не удалось получить origin/main"
    echo "  Выполните: git fetch origin"
    exit 1
fi

echo "Origin (GitHub):"
echo "  HEAD: $ORIGIN_HEAD"
echo "  Коммит: $(git log -1 --oneline origin/main)"
echo ""

# Сравнение
echo "--- РЕЗУЛЬТАТ ---"
if [ "$LOCAL_HEAD" = "$ORIGIN_HEAD" ]; then
    echo "✅ IDENTICAL"
    echo "   Локальная версия совпадает с origin/main"
    echo "   Хэш: $LOCAL_HEAD"
    echo ""
    echo "Примечание: Для проверки сервера выполните на сервере:"
    echo "  cd /var/www/gradaeronaut.com"
    echo "  git rev-parse HEAD"
    echo "  (должен совпадать с $LOCAL_HEAD)"
else
    echo "❌ DESYNC"
    echo "   Локально:  $LOCAL_HEAD"
    echo "   Origin:    $ORIGIN_HEAD"
    echo ""
    echo "Требуется синхронизация:"
    echo "  1. Убедитесь, что все изменения запушены: git push"
    echo "  2. На сервере выполните:"
    echo "     cd /var/www/gradaeronaut.com"
    echo "     git fetch origin"
    echo "     git reset --hard origin/main"
fi

echo ""

