#!/bin/bash
# Скрипт для деплоя на сервер
# Выполнить на сервере: cd /var/www/gradaeronaut.com && bash <(curl -s) или скопировать команды

echo "=== ДЕПЛОЙ НА СЕРВЕР ==="
echo ""

cd /var/www/gradaeronaut.com

echo "1. Получение последних изменений..."
git fetch origin

echo "2. Обновление до последней версии..."
git reset --hard origin/main

echo "3. Проверка последнего коммита..."
git log -1 --oneline

echo "4. Перезагрузка сервисов..."
sudo systemctl reload nginx
sudo systemctl restart php-fpm

echo ""
echo "=== ДЕПЛОЙ ЗАВЕРШЕН ==="
echo ""
echo "Проверьте в браузере:"
echo "- Маркер SERVER-OK-v2 в правом нижнем углу"
echo "- Левый блок: имя + ID · STATUS"
echo "- Аватар: серый фон, белая буква с обводкой"





