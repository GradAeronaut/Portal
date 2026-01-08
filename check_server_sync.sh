#!/bin/bash

# Скрипт для проверки синхронизации локальной версии с сервером

echo "=== Проверка синхронизации Portal26 ==="
echo ""

# Проверка локального статуса
echo "1. Локальный статус Git:"
cd /Users/user/Projects/sinbad26/portal26
git status --short
echo ""

# Проверка последних коммитов
echo "2. Последние 5 коммитов:"
git log --oneline -5
echo ""

# Проверка, что все запушено
echo "3. Проверка синхронизации с origin:"
LOCAL=$(git rev-parse HEAD)
REMOTE=$(git rev-parse origin/main 2>/dev/null || echo "недоступен")

if [ "$LOCAL" = "$REMOTE" ]; then
    echo "✓ Локальная версия синхронизирована с origin/main"
    echo "  Коммит: $LOCAL"
else
    echo "⚠ Локальная версия отличается от origin/main"
    echo "  Локальный: $LOCAL"
    echo "  Удаленный: $REMOTE"
fi
echo ""

# Проверка ключевых файлов
echo "4. Проверка ключевых файлов:"
echo ""

echo "menu.php - inline скрипт для data-location:"
if grep -q "setAttribute.*data-location" public/menu/menu.php; then
    echo "  ✓ Inline скрипт присутствует"
else
    echo "  ✗ Inline скрипт отсутствует"
fi

echo ""
echo "start/index.php - полная страница:"
if grep -q "hero-video-container" public/start/index.php; then
    echo "  ✓ Полная страница с видео"
else
    echo "  ✗ Страница не содержит видео контейнер"
fi

echo ""
echo "start/content.php - заглушка удалена:"
if [ -f "public/start/content.php" ]; then
    echo "  ✗ Файл content.php все еще существует (должен быть удален)"
else
    echo "  ✓ Файл content.php удален"
fi

echo ""
echo "=== Инструкции для синхронизации на сервере ==="
echo ""
echo "На сервере выполните:"
echo "  cd /var/www/gradaeronaut.com"
echo "  git fetch origin"
echo "  git pull origin main"
echo "  sudo systemctl reload nginx"
echo "  sudo systemctl restart php8.1-fpm"
echo ""

