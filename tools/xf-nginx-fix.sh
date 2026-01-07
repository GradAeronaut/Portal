#!/bin/bash
set -e

CONF="/etc/nginx/sites-available/sinbad.conf"

echo "=== 1) Добавляю маркеры если их нет ==="
if ! grep -q "# XF-BEGIN" "$CONF"; then
    echo "" >> "$CONF"
    echo "# XF-BEGIN" >> "$CONF"
    echo "# XF-END" >> "$CONF"
fi

echo "=== 2) Удаляю старый XF-блок между XF-BEGIN и XF-END ==="
sudo sed -i '/# XF-BEGIN/,/# XF-END/{//!d}' "$CONF"

echo "=== 3) Вставляю новый XF-блок ==="
sudo sed -i '/# XF-BEGIN/a \
\
location /forum/ {\
    alias /var/www/gradaeronaut.com/forum/;\
    index index.php;\
    try_files $uri $uri/ @xf;\
}\
\
location @xf {\
    fastcgi_split_path_info ^(.+\.php)(/.*)$;\
    include snippets/fastcgi-php.conf;\
    fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;\
    fastcgi_param SCRIPT_FILENAME /var/www/gradaeronaut.com/forum/index.php;\
}\
' "$CONF"

echo "=== 4) Проверяю конфигурацию NGINX ==="
sudo nginx -t
if [ $? -ne 0 ]; then
    echo "!!! ОШИБКА: nginx -t провалился. Конфиг не перезагружен."
    exit 1
fi

echo "=== 5) Перезагружаю NGINX ==="
sudo systemctl reload nginx

echo "=== ГОТОВО ==="
echo "Теперь открой: http://159.198.74.241/forum/install/"


