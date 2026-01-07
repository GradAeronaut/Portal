#!/bin/bash

set -euo pipefail

echo "== Ищу процессы MariaDB, работающие в skip-grant-tables =="
PIDS=$(ps aux | grep -E "mariadbd|mysqld" | grep skip-grant-tables | awk '{print $2}' || true)

if [ -z "$PIDS" ]; then
    echo "Нет процессов в режиме skip-grant-tables."
else
    echo "Найдены процессы: $PIDS"
    echo "$PIDS" | xargs -r sudo kill -9
    echo "Процессы убиты."
fi

echo "== Убиваю процесс logger, если есть =="
LOGGER=$(ps aux | grep "logger -t mysqld" | awk '{print $2}' || true)
if [ -n "$LOGGER" ]; then
    sudo kill -9 "$LOGGER"
fi

echo "== Проверяю, что процессов больше нет =="
ps aux | grep -E "mariadbd|mysqld" | grep -v grep || true

echo "== Запуск MariaDB =="
sudo systemctl start mariadb

echo "== Состояние MariaDB =="
systemctl status mariadb.service --no-pager


