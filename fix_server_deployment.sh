#!/bin/bash
# Скрипт для автоматического исправления источника деплоя на продакшн сервере
# Запускать на сервере ПОСЛЕ диагностики: bash fix_server_deployment.sh
#
# Скрипт определяет источник деплоя и синхронизирует код с origin/main (sinbad-git-server.git)

set -e

echo "=== ИСПРАВЛЕНИЕ ИСТОЧНИКА ДЕПЛОЯ ==="
echo ""

# Переход в директорию проекта
cd /var/www/gradaeronaut.com || {
    echo "ОШИБКА: Директория /var/www/gradaeronaut.com не найдена"
    exit 1
}

# Определение основного remote
if git remote | grep -q "^origin$"; then
    MAIN_REMOTE="origin"
    echo "✓ Найден remote: origin (sinbad-git-server.git)"
elif git remote | grep -q "^server$"; then
    MAIN_REMOTE="server"
    echo "⚠ Найден remote: server (прямое подключение)"
    echo "  Рекомендуется использовать origin (GitHub) как источник истины"
else
    MAIN_REMOTE=$(git remote | head -1)
    echo "⚠ Используется первый найденный remote: $MAIN_REMOTE"
fi

echo ""

# Проверка текущего коммита
CURRENT_COMMIT=$(git rev-parse HEAD)
echo "Текущий коммит (HEAD): $CURRENT_COMMIT"
git log --oneline -1

echo ""

# Проверка коммита f42182b (до gate)
if git cat-file -e f42182b 2>/dev/null; then
    F42182B_COMMIT=$(git rev-parse f42182b)
    echo "Коммит f42182b (до gate): $F42182B_COMMIT"
    
    if [ "$CURRENT_COMMIT" = "$F42182B_COMMIT" ]; then
        echo "❌ ПРОБЛЕМА: HEAD находится на f42182b (до добавления gate)"
        NEEDS_UPDATE=true
    else
        echo "✓ HEAD не на f42182b"
        NEEDS_UPDATE=false
    fi
else
    echo "⚠ Коммит f42182b не найден в репозитории"
    NEEDS_UPDATE=true
fi

echo ""

# Проверка наличия gate кода
if [ -f "shape-sinbad/index.php" ]; then
    if grep -q "GATE_TEST" shape-sinbad/index.php 2>/dev/null; then
        echo "✓ Файл содержит GATE_TEST"
        HAS_GATE=true
    elif grep -q "GATE_OK" shape-sinbad/index.php 2>/dev/null; then
        echo "⚠ Файл содержит старую версию gate (GATE_OK)"
        HAS_GATE=false
    elif grep -q "session_start" shape-sinbad/index.php 2>/dev/null; then
        echo "⚠ Файл содержит session_start, но нет GATE_TEST"
        HAS_GATE=false
    else
        echo "❌ Файл НЕ содержит gate код"
        HAS_GATE=false
    fi
else
    echo "❌ Файл shape-sinbad/index.php не найден"
    HAS_GATE=false
fi

echo ""

# Определение необходимости обновления
if [ "$NEEDS_UPDATE" = true ] || [ "$HAS_GATE" = false ]; then
    echo "=== ТРЕБУЕТСЯ ОБНОВЛЕНИЕ ==="
    echo ""
    
    # Если есть remote origin - использовать его как источник истины
    if git remote | grep -q "^origin$"; then
        echo "Используется origin (sinbad-git-server.git) как источник истины"
        SOURCE_REMOTE="origin"
    elif [ "$MAIN_REMOTE" != "" ]; then
        echo "Используется $MAIN_REMOTE как источник"
        SOURCE_REMOTE="$MAIN_REMOTE"
    else
        echo "ОШИБКА: Не найден ни один remote"
        exit 1
    fi
    
    echo ""
    echo "Получение последних изменений с $SOURCE_REMOTE..."
    if git fetch "$SOURCE_REMOTE" 2>&1; then
        echo "✓ Fetch выполнен успешно"
    else
        echo "❌ ОШИБКА: Не удалось выполнить git fetch $SOURCE_REMOTE"
        echo "  Проверьте доступ к GitHub или network connection"
        exit 1
    fi
    
    echo ""
    echo "Проверка последнего коммита в $SOURCE_REMOTE/main..."
    if git log "$SOURCE_REMOTE/main" --oneline -1 2>/dev/null; then
        REMOTE_COMMIT=$(git rev-parse "$SOURCE_REMOTE/main")
        echo "  Коммит: $REMOTE_COMMIT"
        
        if [ "$CURRENT_COMMIT" = "$REMOTE_COMMIT" ]; then
            echo "✓ Локальный код уже соответствует $SOURCE_REMOTE/main"
        else
            echo ""
            echo "Обновление до последней версии $SOURCE_REMOTE/main..."
            echo "  Это перезапишет все локальные изменения!"
            
            # Запрос подтверждения
            read -p "Продолжить обновление? (y/N): " -n 1 -r
            echo
            if [[ ! $REPLY =~ ^[Yy]$ ]]; then
                echo "Обновление отменено"
                exit 0
            fi
            
            # Выполнение обновления
            if git reset --hard "$SOURCE_REMOTE/main" 2>&1; then
                echo "✓ Код обновлён до $SOURCE_REMOTE/main"
                echo ""
                echo "Новый коммит (HEAD):"
                git log --oneline -1
            else
                echo "❌ ОШИБКА: Не удалось выполнить git reset --hard"
                exit 1
            fi
        fi
    else
        echo "❌ ОШИБКА: Не удалось получить информацию о $SOURCE_REMOTE/main"
        exit 1
    fi
    
    echo ""
    echo "Проверка gate кода после обновления..."
    if [ -f "shape-sinbad/index.php" ]; then
        if grep -q "GATE_TEST" shape-sinbad/index.php 2>/dev/null; then
            echo "✓ Файл содержит GATE_TEST"
        else
            echo "⚠ ФАЙЛ ВСЁ ЕЩЁ НЕ СОДЕРЖИТ GATE_TEST"
            echo "  Первые 15 строк файла:"
            head -15 shape-sinbad/index.php
        fi
    fi
else
    echo "✓ Код актуален, обновление не требуется"
fi

echo ""
echo "=== ЗАВЕРШЕНО ==="
echo ""
echo "Следующие шаги:"
echo "1. Проверить ответ сервера:"
echo "   curl -I https://gradaeronaut.com/shape-sinbad/"
echo "   Ожидается: HTTP/1.1 401 Unauthorized"
echo ""
echo "2. Если всё работает, можно перезапустить PHP-FPM (опционально):"
echo "   sudo systemctl restart php8.3-fpm"

