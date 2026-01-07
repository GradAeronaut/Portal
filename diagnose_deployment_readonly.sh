#!/bin/bash
# Скрипт ТОЛЬКО для диагностики (read-only, без изменений)
# Запускать на сервере: bash diagnose_deployment_readonly.sh
#
# ВНИМАНИЕ: Этот скрипт НЕ делает никаких изменений
# - НЕ выполняет git reset
# - НЕ выполняет git pull
# - НЕ перезапускает сервисы
# - Только читает информацию и выводит её

set +e  # Не прерывать выполнение при ошибках (для диагностики)

echo "=========================================="
echo "ДИАГНОСТИКА ДЕПЛОЯ (READ-ONLY)"
echo "=========================================="
echo ""

# Переход в директорию проекта
cd /var/www/gradaeronaut.com 2>/dev/null || {
    echo "❌ ОШИБКА: Директория /var/www/gradaeronaut.com не найдена"
    exit 1
}

echo "📁 Текущая директория:"
pwd
echo ""

# 1. Git remotes
echo "=========================================="
echo "1. GIT REMOTES"
echo "=========================================="
echo ""
echo "Все настроенные remotes:"
git remote -v 2>/dev/null || echo "  ОШИБКА: Не удалось получить список remotes"
echo ""

# Определение основного remote
if git remote | grep -q "^origin$" 2>/dev/null; then
    MAIN_REMOTE="origin"
    echo "✓ Найден remote: origin (sinbad-git-server.git)"
elif git remote | grep -q "^server$" 2>/dev/null; then
    MAIN_REMOTE="server"
    echo "✓ Найден remote: server (прямое подключение)"
else
    MAIN_REMOTE=$(git remote 2>/dev/null | head -1)
    if [ -n "$MAIN_REMOTE" ]; then
        echo "⚠ Используется первый найденный remote: $MAIN_REMOTE"
    else
        echo "❌ Remotes не найдены"
        MAIN_REMOTE=""
    fi
fi
echo ""

# 2. Активная ветка
echo "=========================================="
echo "2. АКТИВНАЯ ВЕТКА"
echo "=========================================="
echo ""
CURRENT_BRANCH=$(git branch --show-current 2>/dev/null)
if [ -n "$CURRENT_BRANCH" ]; then
    echo "Текущая ветка: $CURRENT_BRANCH"
    echo "Все локальные ветки:"
    git branch 2>/dev/null || echo "  ОШИБКА: Не удалось получить список веток"
else
    echo "❌ Не удалось определить текущую ветку"
fi
echo ""

# 3. Текущий HEAD
echo "=========================================="
echo "3. ТЕКУЩИЙ HEAD"
echo "=========================================="
echo ""
CURRENT_HEAD=$(git rev-parse HEAD 2>/dev/null)
if [ -n "$CURRENT_HEAD" ]; then
    echo "Коммит (SHA): $CURRENT_HEAD"
    echo "Коммит (краткий): $(git rev-parse --short HEAD 2>/dev/null)"
    echo "Последний коммит:"
    git log --oneline -1 2>/dev/null || echo "  ОШИБКА: Не удалось получить информацию о коммите"
else
    echo "❌ Не удалось определить HEAD"
fi
echo ""

# 4. Проверка коммита f42182b
echo "=========================================="
echo "4. ПРОВЕРКА КОММИТА f42182b"
echo "=========================================="
echo ""
if git cat-file -e f42182b 2>/dev/null; then
    F42182B_SHA=$(git rev-parse f42182b 2>/dev/null)
    echo "✓ Коммит f42182b существует: $F42182B_SHA"
    echo "Информация о коммите f42182b:"
    git log f42182b --oneline -1 2>/dev/null
    
    if [ "$CURRENT_HEAD" = "$F42182B_SHA" ]; then
        echo ""
        echo "❌ HEAD СОВПАДАЕТ С f42182b"
        echo "   Сервер находится на коммите ДО добавления gate"
    else
        echo ""
        echo "✓ HEAD не совпадает с f42182b"
        echo "   Разница между HEAD и f42182b:"
        git log --oneline f42182b..HEAD 2>/dev/null | head -10 || echo "   (HEAD находится до f42182b или нет различий)"
    fi
else
    echo "⚠ Коммит f42182b не найден в репозитории"
fi
echo ""

# 5. Проверка последних коммитов в remote
echo "=========================================="
echo "5. ПОСЛЕДНИЕ КОММИТЫ В REMOTE"
echo "=========================================="
echo ""
if [ -n "$MAIN_REMOTE" ]; then
    echo "Попытка получить информацию из $MAIN_REMOTE/main (только fetch, без merge):"
    git fetch "$MAIN_REMOTE" main 2>&1 | head -5 || echo "  ⚠ Не удалось выполнить git fetch $MAIN_REMOTE (возможно нет доступа или network)"
    echo ""
    
    REMOTE_HEAD=$(git rev-parse "$MAIN_REMOTE/main" 2>/dev/null)
    if [ -n "$REMOTE_HEAD" ]; then
        echo "Последний коммит в $MAIN_REMOTE/main:"
        echo "  SHA: $REMOTE_HEAD"
        git log "$MAIN_REMOTE/main" --oneline -1 2>/dev/null
        
        if [ "$CURRENT_HEAD" = "$REMOTE_HEAD" ]; then
            echo ""
            echo "✓ Локальный HEAD совпадает с $MAIN_REMOTE/main"
        else
            echo ""
            echo "⚠ Локальный HEAD НЕ совпадает с $MAIN_REMOTE/main"
            echo "   Коммиты в $MAIN_REMOTE/main, которых нет локально:"
            git log --oneline HEAD.."$MAIN_REMOTE/main" 2>/dev/null | head -10 || echo "   (нет новых коммитов)"
        fi
    else
        echo "  ⚠ Не удалось получить информацию о $MAIN_REMOTE/main"
    fi
fi

# Проверка origin/main отдельно (если он есть)
if git remote | grep -q "^origin$" 2>/dev/null && [ "$MAIN_REMOTE" != "origin" ]; then
    echo ""
    echo "Проверка origin/main (sinbad-git-server.git - источник истины):"
    git fetch origin main 2>&1 | head -5 || echo "  ⚠ Не удалось выполнить git fetch origin"
    
    ORIGIN_HEAD=$(git rev-parse origin/main 2>/dev/null)
    if [ -n "$ORIGIN_HEAD" ]; then
        echo "Последний коммит в origin/main:"
        echo "  SHA: $ORIGIN_HEAD"
        git log origin/main --oneline -1 2>/dev/null
        
        if [ "$CURRENT_HEAD" = "$ORIGIN_HEAD" ]; then
            echo ""
            echo "✓ Локальный HEAD совпадает с origin/main"
        else
            echo ""
            echo "⚠ Локальный HEAD НЕ совпадает с origin/main"
            echo "   Коммиты в origin/main, которых нет локально:"
            git log --oneline HEAD..origin/main 2>/dev/null | head -10 || echo "   (нет новых коммитов)"
        fi
    fi
fi
echo ""

# 6. Проверка gate кода в файле
echo "=========================================="
echo "6. ПРОВЕРКА GATE КОДА В ФАЙЛЕ"
echo "=========================================="
echo ""
GATE_FILE="shape-sinbad/index.php"
if [ -f "$GATE_FILE" ]; then
    echo "✓ Файл найден: $GATE_FILE"
    echo ""
    
    # Проверка наличия GATE_TEST
    if grep -q "GATE_TEST" "$GATE_FILE" 2>/dev/null; then
        echo "✓ Файл содержит GATE_TEST (тестовый gate код)"
        grep -n "GATE_TEST" "$GATE_FILE" | head -3
    elif grep -q "GATE_OK" "$GATE_FILE" 2>/dev/null; then
        echo "⚠ Файл содержит GATE_OK (старая версия gate)"
        grep -n "GATE_OK" "$GATE_FILE" | head -3
    elif grep -q "session_start" "$GATE_FILE" 2>/dev/null; then
        echo "⚠ Файл содержит session_start, но НЕТ GATE_TEST/GATE_OK"
        echo "   Первые 20 строк файла:"
        head -20 "$GATE_FILE"
    else
        echo "❌ Файл НЕ содержит gate код (ни session_start, ни GATE_TEST/GATE_OK)"
        echo "   Первые 20 строк файла:"
        head -20 "$GATE_FILE"
    fi
else
    echo "❌ Файл НЕ найден: $GATE_FILE"
    echo "   Поиск альтернативных путей:"
    find . -name "index.php" -path "*/shape-sinbad/*" -type f 2>/dev/null | head -5
fi
echo ""

# 7. Проверка автоматического деплоя
echo "=========================================="
echo "7. ПРОВЕРКА АВТОМАТИЧЕСКОГО ДЕПЛОЯ"
echo "=========================================="
echo ""

# Cron задачи
echo "Cron задачи (пользовательские):"
CRON_COUNT=$(crontab -l 2>/dev/null | grep -ic "git\|deploy\|pull" || echo "0")
if [ "$CRON_COUNT" -gt 0 ]; then
    echo "⚠ Найдено $CRON_COUNT cron задач с git/deploy/pull:"
    crontab -l 2>/dev/null | grep -i "git\|deploy\|pull" || true
else
    echo "✓ Нет cron задач с git/deploy/pull"
fi
echo ""

# Systemd timers
echo "Systemd timers:"
TIMER_COUNT=$(systemctl list-timers --all 2>/dev/null | grep -ic "sinbad\|portal\|deploy" || echo "0")
if [ "$TIMER_COUNT" -gt 0 ]; then
    echo "⚠ Найдено $TIMER_COUNT systemd timers для sinbad/portal/deploy:"
    systemctl list-timers --all 2>/dev/null | grep -i "sinbad\|portal\|deploy" || true
else
    echo "✓ Нет systemd timers для sinbad/portal/deploy"
fi
echo ""

# Webhook/autopull скрипты
echo "Поиск webhook/autopull скриптов:"
FOUND_SCRIPTS=0
for path in "/var/www" "/home" "/opt" "/usr/local/bin" "/tmp"; do
    if [ -d "$path" ]; then
        SCRIPTS=$(find "$path" -maxdepth 3 -name "*webhook*" -o -name "*autopull*" -o -name "*deploy*" 2>/dev/null | grep -v ".git" | head -3)
        if [ -n "$SCRIPTS" ]; then
            echo "  Найдено в $path:"
            echo "$SCRIPTS" | while read -r script; do
                echo "    - $script"
                FOUND_SCRIPTS=$((FOUND_SCRIPTS + 1))
            done
        fi
    fi
done
if [ "$FOUND_SCRIPTS" -eq 0 ]; then
    echo "✓ Webhook/autopull скрипты не найдены в стандартных местах"
fi
echo ""

# 8. Итоговая сводка
echo "=========================================="
echo "ИТОГОВАЯ СВОДКА"
echo "=========================================="
echo ""
echo "Источник истины (remote): ${MAIN_REMOTE:-не определён}"
echo "Активная ветка: ${CURRENT_BRANCH:-не определена}"
echo "Текущий HEAD: ${CURRENT_HEAD:-не определён}"
echo ""

if [ -n "$CURRENT_HEAD" ] && [ -n "$F42182B_SHA" ] && [ "$CURRENT_HEAD" = "$F42182B_SHA" ]; then
    echo "❌ ПРОБЛЕМА: Сервер находится на коммите f42182b (ДО добавления gate)"
    echo "   Коммит f42182b: SSO tech debt cleanup"
    echo "   Gate код был добавлен в коммитах после f42182b"
elif [ -n "$CURRENT_HEAD" ]; then
    echo "✓ Сервер НЕ на коммите f42182b"
    if [ -f "$GATE_FILE" ] && grep -q "GATE_TEST\|GATE_OK" "$GATE_FILE" 2>/dev/null; then
        echo "✓ Файл содержит gate код"
    else
        echo "⚠ Файл НЕ содержит gate код (возможно старый коммит или другой путь)"
    fi
fi
echo ""

echo "=========================================="
echo "ДИАГНОСТИКА ЗАВЕРШЕНА (READ-ONLY)"
echo "=========================================="
echo ""
echo "Этот скрипт НЕ внёс никаких изменений в систему."

