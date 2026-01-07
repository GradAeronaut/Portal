#!/bin/bash

# Скрипт проверки баз MariaDB на наличие таблицы users
# 1) Находит все базы MariaDB.
# 2) Проверяет в каждой базе существование таблицы users.
# 3) Если таблица users есть — выводит всех пользователей (id, username, email, public_id).
# 4) Если таблицы users нет ни в одной базы — выводит чёткое сообщение.

set -euo pipefail

# Пароль root для MariaDB/MySQL
DB_PASS="45Root123!"

echo "== Получаю список баз MariaDB =="
DB_LIST=$(mysql -u root -p"$DB_PASS" -N -e "SHOW DATABASES;")

FOUND_DB=false

for DB in $DB_LIST; do
    case "$DB" in
        information_schema|performance_schema|mysql|sys)
            # Системные базы пропускаем
            continue
            ;;
    esac

    # Проверяем наличие таблицы users в базе
    if mysql -u root -p"$DB_PASS" -N -e "SHOW TABLES FROM \`$DB\` LIKE 'users';" 2>/dev/null | grep -q '^users$'; then
        FOUND_DB=true
        echo
        echo "== Найдена таблица users в базе: $DB =="
        mysql -u root -p"$DB_PASS" -e "SELECT id, username, email, public_id FROM \`$DB\`.users;"
    fi
done

if [ "$FOUND_DB" = false ]; then
    echo
    echo "Таблица users не найдена ни в одной базе MariaDB."
fi


