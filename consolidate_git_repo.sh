#!/bin/bash
# Скрипт консолидации Git репозиториев
# Выполняет жёсткую консолидацию: только sinbad-git-server.git

set -e

echo "=========================================="
echo "КОНСОЛИДАЦИЯ GIT РЕПОЗИТОРИЕВ"
echo "=========================================="
echo ""

# Проверка текущей директории
if [ ! -d ".git" ]; then
    echo "❌ ОШИБКА: Это не Git репозиторий"
    exit 1
fi

echo "1. Проверка текущих remotes:"
git remote -v
echo ""

# Проверка наличия gate кода
echo "2. Проверка gate кода:"
if [ -f "shape-sinbad/index.php" ]; then
    if grep -q "GATE_TEST\|GATE_OK\|session_start" shape-sinbad/index.php 2>/dev/null; then
        echo "✓ Файл содержит gate код"
    else
        echo "⚠ Файл НЕ содержит gate код"
    fi
else
    echo "❌ Файл shape-sinbad/index.php не найден"
fi
echo ""

# Проверка текущего коммита
CURRENT_HEAD=$(git rev-parse HEAD)
echo "3. Текущий HEAD: $CURRENT_HEAD"
git log --oneline -1
echo ""

# Проверка наличия коммитов для отправки
echo "4. Проверка коммитов для отправки в origin:"
if git remote | grep -q "^origin$"; then
    git fetch origin main 2>/dev/null || echo "  Предупреждение: не удалось выполнить git fetch origin"
    
    COMMITS_AHEAD=$(git rev-list origin/main..HEAD --count 2>/dev/null || echo "0")
    if [ "$COMMITS_AHEAD" -gt 0 ]; then
        echo "  Найдено коммитов для отправки: $COMMITS_AHEAD"
        echo "  Коммиты:"
        git log origin/main..HEAD --oneline
    else
        echo "  ✓ Все коммиты уже в origin/main"
    fi
else
    echo "  ⚠ Remote 'origin' не найден"
fi
echo ""

# Запрос подтверждения
echo "=========================================="
echo "ПОДТВЕРЖДЕНИЕ ДЕЙСТВИЙ"
echo "=========================================="
echo ""
echo "Будут выполнены следующие действия:"
echo "1. Отправка всех коммитов в origin (sinbad-git-server.git)"
echo "2. Удаление remote 'server' (если существует)"
echo "3. Очистка упоминаний старого GitHub репозитория из документации"
echo ""
read -p "Продолжить? (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Отменено"
    exit 0
fi

# Шаг 1: Отправка коммитов в origin
echo ""
echo "=========================================="
echo "ШАГ 1: Отправка коммитов в origin"
echo "=========================================="
if git remote | grep -q "^origin$"; then
    ORIGIN_URL=$(git remote get-url origin)
    echo "Remote origin: $ORIGIN_URL"
    
    if [ "$COMMITS_AHEAD" -gt 0 ]; then
        echo "Отправка коммитов..."
        if git push origin main; then
            echo "✓ Коммиты успешно отправлены в origin/main"
        else
            echo "❌ ОШИБКА: Не удалось отправить коммиты"
            exit 1
        fi
    else
        echo "✓ Все коммиты уже в origin/main"
    fi
    
    # Проверка что gate код в origin
    echo ""
    echo "Проверка gate кода в origin/main:"
    git fetch origin main
    if git show origin/main:shape-sinbad/index.php 2>/dev/null | grep -q "GATE_TEST\|GATE_OK\|session_start"; then
        echo "✓ origin/main содержит gate код"
    else
        echo "⚠ origin/main НЕ содержит gate код"
    fi
else
    echo "❌ Remote 'origin' не найден, пропуск шага 1"
fi

echo ""
echo "=========================================="
echo "ШАГ 2: Удаление remote 'server'"
echo "=========================================="
if git remote | grep -q "^server$"; then
    echo "Удаление remote 'server'..."
    git remote remove server
    echo "✓ Remote 'server' удалён"
else
    echo "✓ Remote 'server' не найден (уже удалён или не был создан)"
fi

echo ""
echo "Проверка текущих remotes:"
git remote -v
echo ""

echo "=========================================="
echo "ШАГ 3: Фиксация канонического коммита"
echo "=========================================="
CANONICAL_COMMIT=$(git rev-parse HEAD)
echo "Канонический коммит: $CANONICAL_COMMIT"
git log --oneline -1
echo ""
echo "✓ Коммит зафиксирован как канонический"
echo ""

echo "=========================================="
echo "КОНСОЛИДАЦИЯ ЗАВЕРШЕНА"
echo "=========================================="
echo ""
echo "Текущие remotes:"
git remote -v
echo ""
echo "Следующий шаг: Очистка документации (выполняется отдельно)"



