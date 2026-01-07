#!/bin/bash
set -euo pipefail

BASE="/var/www/gradaeronaut.com"
FORUM="$BASE/forum"
ZIP="$BASE/xenforo_2.3.7_1E6DEF224B_full (2).zip"

echo "== Проверка архива =="
if [ ! -f "$ZIP" ]; then
  echo "Архив не найден: $ZIP"; exit 1;
fi

echo "== Подготовка каталога форума =="
rm -rf "$FORUM"
mkdir -p "$FORUM"

echo "== Распаковка XenForo =="
unzip -q "$ZIP" -d "$BASE"

if [ ! -d "$BASE/upload" ]; then
  echo "ОШИБКА: upload/ отсутствует после распаковки"; exit 1;
fi

echo "== Перемещение файлов =="
mv "$BASE/upload/"* "$FORUM"/

echo "== Очистка =="
rm -rf "$BASE/upload"

echo "== Проверка структуры =="
NEEDED=( "index.php" "src" "js" "styles" "internal_data" "data" )
for item in "${NEEDED[@]}"; do
  if [ ! -e "$FORUM/$item" ]; then
    echo "ОШИБКА: отсутствует $item в $FORUM"; exit 1;
  fi
done

echo "== Права каталогов =="
chown -R www-data:www-data "$FORUM"
chmod -R 755 "$FORUM"
chmod -R 777 "$FORUM/data" "$FORUM/internal_data"

echo "== ГОТОВО =="
echo "Открой в браузере: https://gradaeronaut.com/forum/install.php"
