#!/bin/bash
# Скрипт диагностики маршрутизации PHP в nginx для /app/

echo "=== Шаг 1: Проверка HTTP-ответов ==="
echo ""
echo "1. Проверка register.php:"
curl -i https://gradaeronaut.com/app/register.php 2>&1 | head -20
echo ""
echo "---"
echo ""
echo "2. Проверка resend_verification.php:"
curl -i https://gradaeronaut.com/app/resend_verification.php 2>&1 | head -20
echo ""
echo "=== Шаг 2: Проверка nginx конфигурации ==="
echo ""
echo "1. Поиск конфига для gradaeronaut.com:"
grep -R "server_name gradaeronaut.com" /etc/nginx/ 2>/dev/null | head -5
echo ""
echo "2. Поиск location /app:"
grep -R "location /app" /etc/nginx/ 2>/dev/null
echo ""
echo "3. Проверка location ~ \.php\$ в найденных конфигах:"
for conf in $(grep -l "server_name gradaeronaut.com" /etc/nginx/sites-enabled/* /etc/nginx/sites-available/* 2>/dev/null); do
    echo "--- Конфиг: $conf ---"
    grep -A 5 "location ~ \\\.php" "$conf" 2>/dev/null || echo "Не найден location ~ \.php\$"
    echo ""
done
echo ""
echo "=== Шаг 3: Проверка физических путей ==="
echo ""
echo "1. Проверка root в nginx конфиге:"
for conf in $(grep -l "server_name gradaeronaut.com" /etc/nginx/sites-enabled/* /etc/nginx/sites-available/* 2>/dev/null); do
    echo "--- Конфиг: $conf ---"
    grep "root " "$conf" 2>/dev/null
    echo ""
done
echo ""
echo "2. Проверка существования файлов:"
echo "Проверка /var/www/gradaeronaut.com/app/register.php:"
ls -la /var/www/gradaeronaut.com/app/register.php 2>&1
echo ""
echo "Проверка /var/www/gradaeronaut.com/public/app/register.php:"
ls -la /var/www/gradaeronaut.com/public/app/register.php 2>&1
echo ""
echo "=== Шаг 4: Проверка PHP-FPM ==="
echo ""
echo "Проверка сокета PHP-FPM:"
ls -la /run/php/php8.3-fpm.sock 2>&1 || ls -la /run/php/php*-fpm.sock 2>&1 | head -3
echo ""
echo "=== Готово ==="



