#!/bin/bash

set -euo pipefail

echo "== Ищу конфигурационные файлы портала =="
grep -R "dbname" -n /var/www/gradaeronaut.com || true
grep -R "database" -n /var/www/gradaeronaut.com || true
grep -R "DB_NAME" -n /var/www/gradaeronaut.com || true
grep -R "PDO" -n /var/www/gradaeronaut.com || true

echo "== Готово. Найденные строки выше. =="


