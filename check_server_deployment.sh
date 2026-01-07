#!/bin/bash
# Скрипт для проверки источника деплоя на продакшн сервере
# Запускать на сервере: bash check_server_deployment.sh

set -e

echo "=== ПРОВЕРКА ИСТОЧНИКА ДЕПЛОЯ ==="
echo ""

# Переход в директорию проекта
cd /var/www/gradaeronaut.com || {
    echo "ОШИБКА: Директория /var/www/gradaeronaut.com не найдена"
    exit 1
}

echo "1. Текущая директория:"
pwd
echo ""

echo "2. Git remotes:"
git remote -v
echo ""

echo "2.1. Определение основного remote:"
if git remote | grep -q "^origin$"; then
    MAIN_REMOTE="origin"
    echo "  Основной remote: origin (sinbad-git-server.git)"
elif git remote | grep -q "^server$"; then
    MAIN_REMOTE="server"
    echo "  Основной remote: server (прямое подключение)"
else
    MAIN_REMOTE=$(git remote | head -1)
    echo "  Основной remote: $MAIN_REMOTE (первый найденный)"
fi
echo ""

echo "3. Текущая ветка:"
git branch --show-current
echo ""

echo "4. Текущий коммит (HEAD):"
git rev-parse HEAD
git log --oneline -1
echo ""

echo "5. Последний коммит в $MAIN_REMOTE/main:"
git fetch "$MAIN_REMOTE" 2>/dev/null || echo "Не удалось выполнить git fetch $MAIN_REMOTE (возможно нет доступа)"
git log "$MAIN_REMOTE/main" --oneline -1 2>/dev/null || echo "Ветка $MAIN_REMOTE/main не найдена"

# Также проверить origin/main если доступен (sinbad-git-server.git - источник правды)
if [ "$MAIN_REMOTE" != "origin" ] && git remote | grep -q "^origin$"; then
    echo "  (Также проверка origin/main - sinbad-git-server.git):"
    git fetch origin 2>/dev/null || echo "  Не удалось выполнить git fetch origin"
    git log origin/main --oneline -1 2>/dev/null || echo "  Ветка origin/main не найдена"
fi
echo ""

echo "6. Проверка коммита f42182b:"
if git cat-file -e f42182b 2>/dev/null; then
    echo "  Коммит f42182b существует:"
    git log f42182b --oneline -1
    echo "  Текущий HEAD совпадает с f42182b?"
    if [ "$(git rev-parse HEAD)" = "$(git rev-parse f42182b)" ]; then
        echo "  ✅ ДА - HEAD = f42182b"
    else
        echo "  ❌ НЕТ - HEAD != f42182b"
    fi
else
    echo "  Коммит f42182b не найден"
fi
echo ""

echo "7. Проверка наличия gate в текущем файле:"
if [ -f "shape-sinbad/index.php" ]; then
    echo "  Файл существует"
    if grep -q "GATE_TEST" shape-sinbad/index.php 2>/dev/null; then
        echo "  ✅ Содержит GATE_TEST"
    elif grep -q "GATE_OK" shape-sinbad/index.php 2>/dev/null; then
        echo "  ⚠️  Содержит GATE_OK (старая версия)"
    elif grep -q "session_start" shape-sinbad/index.php 2>/dev/null; then
        echo "  ✅ Содержит session_start"
        echo "  Первые 15 строк файла:"
        head -15 shape-sinbad/index.php
    else
        echo "  ❌ Не содержит gate код"
    fi
else
    echo "  ❌ Файл shape-sinbad/index.php не найден"
    echo "  Проверка альтернативных путей:"
    find . -name "index.php" -path "*/shape-sinbad/*" -type f 2>/dev/null | head -5
fi
echo ""

echo "8. Проверка автоматического деплоя:"
echo "  Проверка cron задач:"
crontab -l 2>/dev/null | grep -i "git\|deploy\|pull" || echo "  Нет cron задач с git/deploy/pull"
echo ""

echo "  Проверка systemd timers:"
systemctl list-timers --all 2>/dev/null | grep -i "sinbad\|portal\|deploy" || echo "  Нет systemd timers для sinbad/portal/deploy"
echo ""

echo "  Проверка webhook скриптов (общие места):"
for path in "/var/www" "/home" "/opt" "/usr/local/bin"; do
    if [ -d "$path" ]; then
        find "$path" -name "*webhook*" -o -name "*autopull*" -o -name "*deploy*" 2>/dev/null | head -3
    fi
done
echo ""

echo "=== ЗАВЕРШЕНО ==="
echo ""
echo "Следующие шаги:"
echo "1. Если HEAD != последний коммит $MAIN_REMOTE/main → выполнить:"
echo "   git fetch $MAIN_REMOTE && git reset --hard $MAIN_REMOTE/main"
if [ "$MAIN_REMOTE" != "origin" ] && git remote | grep -q "^origin$"; then
    echo "   ИЛИ использовать origin (sinbad-git-server.git - источник правды):"
    echo "   git fetch origin && git reset --hard origin/main"
fi
echo "2. Если файл не содержит gate код → проверить правильность пути"
echo "3. Если есть auto-pull/webhook → проверить его конфигурацию"

