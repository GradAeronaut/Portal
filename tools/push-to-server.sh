#!/bin/bash
#
# Push to Server
# Отправляет изменения на production сервер
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

echo -e "${BLUE}📤 Отправка изменений на сервер...${NC}"
echo "Server: root@159.198.74.241:/var/www/gradaeronaut.com"
echo ""

# Проверяем текущую ветку
CURRENT_BRANCH=$(git branch --show-current)
echo "📍 Текущая ветка: $CURRENT_BRANCH"

# Проверяем незакоммиченные изменения
if ! git diff-index --quiet HEAD --; then
    echo -e "${RED}❌ PUSH ЗАБЛОКИРОВАН${NC}"
    echo "Обнаружены незакоммиченные изменения."
    echo "Сначала закоммитьте изменения:"
    echo ""
    git status --short
    exit 1
fi

# Fetch с сервера для проверки
echo -e "${YELLOW}🔍 Проверяю состояние сервера...${NC}"
git fetch "$SERVER_REMOTE" "$SERVER_BRANCH" || {
    echo -e "${RED}❌ Ошибка при подключении к серверу${NC}"
    exit 1
}

# Проверяем, не отстали ли мы от сервера
LOCAL=$(git rev-parse HEAD)
REMOTE=$(git rev-parse "$SERVER_REMOTE/$SERVER_BRANCH")
BASE=$(git merge-base HEAD "$SERVER_REMOTE/$SERVER_BRANCH")

if [ "$LOCAL" = "$REMOTE" ]; then
    echo -e "${GREEN}✅ Нет новых коммитов для отправки${NC}"
    exit 0
fi

if [ "$BASE" != "$REMOTE" ]; then
    echo -e "${RED}❌ PUSH ЗАБЛОКИРОВАН${NC}"
    echo "На сервере есть новые коммиты, которых нет локально."
    echo "Сначала выполните pull:"
    echo "  ./tools/pull-from-server.sh"
    exit 1
fi

# Показываем что будем отправлять
echo -e "${YELLOW}📊 Коммиты для отправки:${NC}"
git log --oneline "$SERVER_REMOTE/$SERVER_BRANCH"..HEAD

echo ""
read -p "Отправить эти изменения на production сервер? (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Push отменён"
    exit 0
fi

# Push на сервер
echo -e "${YELLOW}🚀 Отправляю изменения...${NC}"
git push "$SERVER_REMOTE" "$CURRENT_BRANCH:$SERVER_BRANCH" || {
    echo -e "${RED}❌ Ошибка при push на сервер${NC}"
    exit 1
}

echo -e "${GREEN}✅ Изменения успешно отправлены на сервер${NC}"

# Опционально: перезапуск сервисов на сервере
echo ""
read -p "Перезапустить Nginx и PHP-FPM на сервере? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${YELLOW}🔄 Перезапускаю сервисы...${NC}"
    ssh root@159.198.74.241 "systemctl reload nginx && systemctl restart php8.3-fpm"
    echo -e "${GREEN}✅ Сервисы перезапущены${NC}"
fi

exit 0
