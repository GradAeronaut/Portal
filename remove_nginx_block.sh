#!/bin/bash
# Скрипт для удаления server-блока из nginx конфигурации
# Использование: sudo bash remove_nginx_block.sh

CONFIG_FILE="/etc/nginx/sites-enabled/sinbad.conf"

if [ ! -f "$CONFIG_FILE" ]; then
    echo "Ошибка: файл $CONFIG_FILE не найден"
    exit 1
fi

# Создаем резервную копию
BACKUP_FILE="${CONFIG_FILE}.backup.$(date +%Y%m%d_%H%M%S)"
cp "$CONFIG_FILE" "$BACKUP_FILE"
echo "Создана резервная копия: $BACKUP_FILE"

# Используем awk для удаления блока
awk '
/server \{/ {
    block_start = NR
    in_block = 1
    block_lines = $0 "\n"
    next
}
in_block {
    block_lines = block_lines $0 "\n"
    if ($0 ~ /^}$/) {
        # Проверяем, содержит ли блок нужные строки
        if (block_lines ~ /server_name gradaeronaut\.com www\.gradaeronaut\.com 159\.198\.74\.241;/ && 
            block_lines ~ /return 404; # managed by Certbot/) {
            # Пропускаем этот блок (не выводим)
            in_block = 0
            block_lines = ""
            next
        } else {
            # Выводим блок
            printf "%s", block_lines
            in_block = 0
            block_lines = ""
            next
        }
    }
    next
}
{
    print
}
' "$CONFIG_FILE" > "${CONFIG_FILE}.tmp" && mv "${CONFIG_FILE}.tmp" "$CONFIG_FILE"

echo "Блок удален. Проверьте конфигурацию: sudo nginx -t"
echo "Если все ОК, перезагрузите nginx: sudo systemctl reload nginx"


