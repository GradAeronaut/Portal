#!/bin/bash
#
# Sync with Server (bidirectional)
# Двусторонняя синхронизация с сервером
#

set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
LOCAL_DIR="/Users/user/Desktop/sinbad-portal"

# Цвета
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

cd "$LOCAL_DIR"

echo -e "${BLUE}═══════════════════════════════════════${NC}"
echo -e "${BLUE}  Двусторонняя синхронизация с сервером${NC}"
echo -e "${BLUE}═══════════════════════════════════════${NC}"
echo ""

# Шаг 1: Создаём бэкап перед синхронизацией
echo -e "${YELLOW}📦 Шаг 1: Создание бэкапа...${NC}"
.git/hooks/backup-manager.sh create
echo ""

# Шаг 2: Получаем изменения с сервера
echo -e "${YELLOW}📥 Шаг 2: Получение изменений с сервера...${NC}"
"$SCRIPT_DIR/pull-from-server.sh" || {
    echo -e "${RED}❌ Ошибка при получении изменений с сервера${NC}"
    exit 1
}
echo ""

# Шаг 3: Проверяем наличие локальных изменений для отправки
echo -e "${YELLOW}📤 Шаг 3: Проверка локальных изменений...${NC}"

git fetch server main
LOCAL=$(git rev-parse HEAD)
REMOTE=$(git rev-parse server/main)

if [ "$LOCAL" = "$REMOTE" ]; then
    echo -e "${GREEN}✅ Синхронизация завершена - нет изменений для отправки${NC}"
else
    echo -e "${YELLOW}Обнаружены локальные коммиты для отправки${NC}"
    echo ""
    read -p "Отправить локальные изменения на сервер? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        "$SCRIPT_DIR/push-to-server.sh"
    else
        echo "Отправка отменена"
    fi
fi

echo ""
echo -e "${GREEN}═══════════════════════════════════════${NC}"
echo -e "${GREEN}  ✅ Синхронизация завершена${NC}"
echo -e "${GREEN}═══════════════════════════════════════${NC}"

exit 0
