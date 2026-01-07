#!/bin/bash
#
# Pull from Server
# Получает изменения с production сервера
#

set -e

SERVER_REMOTE="server"
SERVER_BRANCH="main"
LOCAL_DIR="/Users/user/Desktop/sinbad-portal"

# Цвета
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

cd "$LOCAL_DIR"

echo -e "${BLUE}📥 Получение изменений с сервера...${NC}"
echo "Server: root@159.198.74.241:/var/www/gradaeronaut.com"
echo ""

# Проверяем текущую ветку
CURRENT_BRANCH=$(git branch --show-current)
echo "📍 Текущая ветка: $CURRENT_BRANCH"

# Проверяем незакоммиченные изменения
if ! git diff-index --quiet HEAD --; then
    echo -e "${YELLOW}⚠️  Обнаружены незакоммиченные изменения${NC}"
    echo "Сохраняю их в stash..."
    git stash push -m "Auto-stash before pull from server $(date +%Y-%m-%d_%H-%M-%S)"
    STASHED=true
else
    STASHED=false
fi

# Fetch с сервера
echo -e "${YELLOW}🔍 Проверяю изменения на сервере...${NC}"
git fetch "$SERVER_REMOTE" "$SERVER_BRANCH" || {
    echo -e "${RED}❌ Ошибка при подключении к серверу${NC}"
    exit 1
}

# Проверяем наличие новых коммитов
LOCAL=$(git rev-parse HEAD)
REMOTE=$(git rev-parse "$SERVER_REMOTE/$SERVER_BRANCH")

if [ "$LOCAL" = "$REMOTE" ]; then
    echo -e "${GREEN}✅ Локальная версия актуальна${NC}"
    
    if [ "$STASHED" = true ]; then
        echo "Восстанавливаю изменения из stash..."
        git stash pop
    fi
    
    exit 0
fi

# Показываем разницу
echo -e "${YELLOW}📊 Новые коммиты на сервере:${NC}"
git log --oneline HEAD.."$SERVER_REMOTE/$SERVER_BRANCH" | head -10

echo ""
echo -e "${YELLOW}🔄 Выполняю merge изменений с сервера...${NC}"

# Merge изменений
git merge "$SERVER_REMOTE/$SERVER_BRANCH" --no-edit || {
    echo -e "${RED}❌ Конфликт при merge!${NC}"
    echo "Разрешите конфликты вручную и выполните:"
    echo "  git merge --continue"
    exit 1
}

echo -e "${GREEN}✅ Изменения с сервера успешно получены${NC}"

# Восстанавливаем stash если был
if [ "$STASHED" = true ]; then
    echo "Восстанавливаю изменения из stash..."
    git stash pop || {
        echo -e "${YELLOW}⚠️  Конфликт при восстановлении stash${NC}"
        echo "Разрешите конфликты вручную"
    }
fi

# Показываем статус
echo ""
echo -e "${BLUE}📋 Текущий статус:${NC}"
git status --short

exit 0
