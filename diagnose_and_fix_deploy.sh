#!/bin/bash

# Скрипт диагностики и исправления автодеплоя
# Проверяет идентичность версий и исправляет автодеплой при необходимости

set -e

LOCAL_REPO="/Users/user/Projects/sinbad26/portal26"
SERVER_REPO="/var/www/gradaeronaut.com"
REPO_NAME="Portal"
GITHUB_REPO="GradAeronaut/Portal"

echo "=== ДИАГНОСТИКА АВТОДЕПЛОЯ ==="
echo ""

# Часть 1: Проверка идентичности версий
echo "--- ЧАСТЬ 1: ПРОВЕРКА ИДЕНТИЧНОСТИ ВЕРСИЙ ---"
echo ""

# Локальный HEAD
cd "$LOCAL_REPO"
LOCAL_HEAD=$(git rev-parse HEAD)
LOCAL_ORIGIN_HEAD=$(git rev-parse origin/main 2>/dev/null || echo "недоступен")

echo "Локально:"
echo "  HEAD: $LOCAL_HEAD"
echo "  origin/main: $LOCAL_ORIGIN_HEAD"
echo ""

# Проверка подключения к серверу и получение серверного HEAD
echo "Проверка сервера..."
SERVER_HEAD=""
if ssh -o ConnectTimeout=5 -o BatchMode=yes gradaeronaut.com "cd $SERVER_REPO && git rev-parse HEAD 2>/dev/null" 2>/dev/null; then
    SERVER_HEAD=$(ssh gradaeronaut.com "cd $SERVER_REPO && git rev-parse HEAD 2>/dev/null")
    echo "Сервер:"
    echo "  HEAD: $SERVER_HEAD"
    echo ""
else
    echo "⚠ Не удалось подключиться к серверу для проверки HEAD"
    echo "  (требуется SSH доступ или выполнение на сервере)"
    echo ""
fi

# Сравнение
echo "--- РЕЗУЛЬТАТ СРАВНЕНИЯ ---"
if [ -n "$SERVER_HEAD" ]; then
    if [ "$LOCAL_HEAD" = "$SERVER_HEAD" ]; then
        echo "✅ IDENTICAL"
        echo "   Версии совпадают: $LOCAL_HEAD"
        exit 0
    else
        echo "❌ DESYNC"
        echo "   Локально:  $LOCAL_HEAD"
        echo "   Сервер:   $SERVER_HEAD"
        echo "   Origin:   $LOCAL_ORIGIN_HEAD"
    fi
else
    echo "⚠ НЕВОЗМОЖНО ОПРЕДЕЛИТЬ"
    echo "   Локально:  $LOCAL_HEAD"
    echo "   Origin:    $LOCAL_ORIGIN_HEAD"
    echo "   (требуется проверка на сервере вручную)"
fi
echo ""

# Часть 2: Проверка автоматики (если DESYNC)
if [ -n "$SERVER_HEAD" ] && [ "$LOCAL_HEAD" != "$SERVER_HEAD" ]; then
    echo "--- ЧАСТЬ 2: ПРОВЕРКА АВТОМАТИКИ ---"
    echo ""
    
    # Проверка webhook на GitHub
    echo "1. Проверка GitHub Webhook..."
    WEBHOOK_URL="https://api.github.com/repos/$GITHUB_REPO/hooks"
    echo "   Проверка: $WEBHOOK_URL"
    echo "   (требуется GitHub token для полной проверки)"
    echo ""
    
    # Проверка deploy скрипта на сервере
    echo "2. Проверка deploy скрипта на сервере..."
    DEPLOY_SCRIPTS=(
        "$SERVER_REPO/.github/hooks/deploy.sh"
        "$SERVER_REPO/deploy.sh"
        "$SERVER_REPO/hooks/deploy.sh"
        "/var/www/deploy-$REPO_NAME.sh"
        "/usr/local/bin/deploy-$REPO_NAME.sh"
    )
    
    DEPLOY_SCRIPT=""
    for script in "${DEPLOY_SCRIPTS[@]}"; do
        if ssh gradaeronaut.com "test -f $script" 2>/dev/null; then
            DEPLOY_SCRIPT="$script"
            echo "   ✓ Найден: $script"
            break
        fi
    done
    
    if [ -z "$DEPLOY_SCRIPT" ]; then
        echo "   ✗ Deploy скрипт не найден"
    fi
    echo ""
    
    # Проверка содержимого deploy скрипта
    if [ -n "$DEPLOY_SCRIPT" ]; then
        echo "3. Проверка содержимого deploy скрипта..."
        SCRIPT_CONTENT=$(ssh gradaeronaut.com "cat $DEPLOY_SCRIPT" 2>/dev/null)
        
        if echo "$SCRIPT_CONTENT" | grep -q "git fetch"; then
            echo "   ✓ Содержит: git fetch"
        else
            echo "   ✗ НЕ содержит: git fetch"
        fi
        
        if echo "$SCRIPT_CONTENT" | grep -q "git reset --hard origin/main"; then
            echo "   ✓ Содержит: git reset --hard origin/main"
        else
            echo "   ✗ НЕ содержит: git reset --hard origin/main"
        fi
        
        if echo "$SCRIPT_CONTENT" | grep -q "$SERVER_REPO"; then
            echo "   ✓ Указывает на правильную директорию: $SERVER_REPO"
        else
            echo "   ⚠ Может указывать на неправильную директорию"
        fi
        echo ""
    fi
    
    # Проверка логов
    echo "4. Проверка логов автодеплоя..."
    LOG_FILES=(
        "/var/log/deploy-$REPO_NAME.log"
        "/var/log/github-webhook.log"
        "$SERVER_REPO/deploy.log"
        "/tmp/deploy.log"
    )
    
    for log_file in "${LOG_FILES[@]}"; do
        if ssh gradaeronaut.com "test -f $log_file" 2>/dev/null; then
            echo "   ✓ Найден лог: $log_file"
            echo "   Последние строки:"
            ssh gradaeronaut.com "tail -5 $log_file 2>/dev/null" | sed 's/^/     /'
            echo ""
        fi
    done
    echo ""
    
    # Часть 3: Автоисправление
    echo "--- ЧАСТЬ 3: АВТОИСПРАВЛЕНИЕ ---"
    echo ""
    
    # Создание каноничного deploy скрипта
    CANONICAL_DEPLOY_SCRIPT="$SERVER_REPO/deploy.sh"
    CANONICAL_SCRIPT_CONTENT="#!/bin/bash
set -e
cd $SERVER_REPO
git fetch origin
git reset --hard origin/main
# Опционально: перезагрузка сервисов
# sudo systemctl reload nginx 2>/dev/null || true
# sudo systemctl restart php8.1-fpm 2>/dev/null || true
"
    
    if [ -z "$DEPLOY_SCRIPT" ] || ! echo "$SCRIPT_CONTENT" | grep -q "git reset --hard origin/main"; then
        echo "Создание/исправление deploy скрипта..."
        ssh gradaeronaut.com "cat > $CANONICAL_DEPLOY_SCRIPT << 'DEPLOY_EOF'
$CANONICAL_SCRIPT_CONTENT
DEPLOY_EOF
chmod +x $CANONICAL_DEPLOY_SCRIPT" 2>/dev/null
        
        if [ $? -eq 0 ]; then
            echo "   ✓ Deploy скрипт создан/обновлен: $CANONICAL_DEPLOY_SCRIPT"
        else
            echo "   ✗ Не удалось создать deploy скрипт (требуются права)"
        fi
        echo ""
    fi
    
    # Запуск deploy скрипта
    if ssh gradaeronaut.com "test -x $CANONICAL_DEPLOY_SCRIPT" 2>/dev/null; then
        echo "Запуск deploy скрипта..."
        ssh gradaeronaut.com "cd $SERVER_REPO && bash $CANONICAL_DEPLOY_SCRIPT" 2>&1 | sed 's/^/   /'
        
        # Повторная проверка HEAD
        sleep 2
        NEW_SERVER_HEAD=$(ssh gradaeronaut.com "cd $SERVER_REPO && git rev-parse HEAD 2>/dev/null")
        
        echo ""
        echo "--- ФИНАЛЬНАЯ ПРОВЕРКА ---"
        if [ "$LOCAL_HEAD" = "$NEW_SERVER_HEAD" ]; then
            echo "✅ AUTODEPLOY FIXED"
            echo "   Версии синхронизированы: $LOCAL_HEAD"
        else
            echo "❌ STILL BROKEN"
            echo "   Локально:  $LOCAL_HEAD"
            echo "   Сервер:    $NEW_SERVER_HEAD"
            echo "   Причина:   Возможно, требуется ручная синхронизация или проверка прав доступа"
        fi
    else
        echo "⚠ Не удалось запустить deploy скрипт"
        echo "   Требуется выполнить вручную на сервере:"
        echo "   cd $SERVER_REPO"
        echo "   git fetch origin"
        echo "   git reset --hard origin/main"
    fi
fi

echo ""
echo "=== ДИАГНОСТИКА ЗАВЕРШЕНА ==="

