#!/bin/bash

# Скрипт для добавления маршрута SSO в БД XenForo
# Выполнить на сервере

# Получить данные БД из конфига
CONFIG_FILE="/var/www/gradaeronaut.com/forum/src/config.php"

if [ ! -f "$CONFIG_FILE" ]; then
    echo "Ошибка: файл конфигурации не найден: $CONFIG_FILE"
    exit 1
fi

DB_NAME=$(grep dbname "$CONFIG_FILE" | cut -d"'" -f4)
DB_USER=$(grep dbusername "$CONFIG_FILE" | cut -d"'" -f4)
DB_PASS=$(grep dbpassword "$CONFIG_FILE" | cut -d"'" -f4)

if [ -z "$DB_NAME" ] || [ -z "$DB_USER" ] || [ -z "$DB_PASS" ]; then
    echo "Ошибка: не удалось получить данные БД из конфига"
    exit 1
fi

echo "Данные БД:"
echo "  Database: $DB_NAME"
echo "  User: $DB_USER"
echo ""

# Проверить, существует ли маршрут
EXISTING=$(mysql -u "$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME; SELECT route_id FROM xf_route WHERE route_type = 'public' AND route_prefix = 'sso' AND sub_name = 'forward';" 2>/dev/null | tail -n +2)

if [ -n "$EXISTING" ]; then
    echo "Маршрут уже существует (route_id: $EXISTING)"
    echo "Удаляем существующий маршрут..."
    mysql -u "$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME; DELETE FROM xf_route WHERE route_type = 'public' AND route_prefix = 'sso' AND sub_name = 'forward';" 2>/dev/null
fi

# Добавить маршрут в xf_route
echo "Добавляем маршрут..."
mysql -u "$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME; INSERT INTO xf_route (route_type, route_prefix, sub_name, format, build_class, build_method, controller, context, action_prefix, addon_id) VALUES ('public', 'sso', 'forward', '', '', '', 'Sinbad\\\\SSO:Forward', '', '', 'Sinbad/SSO');" 2>/dev/null

if [ $? -eq 0 ]; then
    echo "✓ Маршрут успешно добавлен!"
    echo ""
    echo "Проверка:"
    mysql -u "$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME; SELECT route_id, route_type, route_prefix, sub_name, controller, addon_id FROM xf_route WHERE route_prefix = 'sso';" 2>/dev/null
else
    echo "✗ Ошибка при добавлении маршрута"
    exit 1
fi


