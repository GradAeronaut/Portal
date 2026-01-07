#!/bin/bash

set -euo pipefail

echo "== УБИВАЮ ВСЕ ПРОЦЕССЫ MARIADB И MYSQL =="
sudo killall -9 mariadbd mysqld mysqld_safe logger || true

echo "== ПРОВЕРЯЮ, ЧТО ПРОЦЕССОВ НЕ ОСТАЛОСЬ =="
ps aux | grep -E "mariadbd|mysqld" | grep -v grep || true

echo "== ЗАПУСКАЮ MARIADB НОРМАЛЬНО =="
sudo systemctl start mariadb

echo "== СТАТУС MARIADB =="
systemctl status mariadb.service --no-pager


