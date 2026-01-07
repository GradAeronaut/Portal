#!/bin/bash

set -euo pipefail

DB_PASS="45Root123!"

echo "== Получаю список баз =="
mysql -u root -p$DB_PASS -e "SHOW DATABASES;"

echo
echo "== Поиск таблицы users во всех базах =="
DB=$(mysql -u root -p$DB_PASS -e "SHOW DATABASES;" | grep -v schema | grep -v mysql | grep -v Database | while read db; do
    mysql -u root -p$DB_PASS -e "SHOW TABLES FROM $db;" 2>/dev/null | grep -q users && echo $db
done)

if [ -z "$DB" ]; then
    echo "Таблица users не найдена ни в одной базе."
    exit 1
fi

echo "== Таблица users найдена в базе: $DB =="

echo
echo "== Получаю public_id всех пользователей =="
mysql -u root -p$DB_PASS -e "SELECT id, username, email, public_id FROM $DB.users;"

echo
echo "== Проверка завершена. External_id выведен выше. =="


