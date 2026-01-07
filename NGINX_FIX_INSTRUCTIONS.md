# Инструкция по исправлению nginx для /app/

## Быстрая диагностика

Выполните на сервере:

```bash
# 1. Проверка HTTP-ответов
curl -i https://gradaeronaut.com/app/register.php
curl -i https://gradaeronaut.com/app/resend_verification.php

# 2. Автоматическая диагностика (если скрипт загружен на сервер)
./check_nginx_php_routing.sh
```

## Вариант 1: Если файлы в /var/www/gradaeronaut.com/app/ (не в public/)

Добавьте в nginx конфиг блок для /app/ **ПЕРЕД** `location ~ \.php$`:

```nginx
# APP - обработка /app/*.php файлов
location /app/ {
    root /var/www/gradaeronaut.com;
    try_files $uri $uri/ =404;
    
    location ~ ^/app/.*\.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME /var/www/gradaeronaut.com$fastcgi_script_name;
    }
}
```

## Вариант 2: Если файлы в /var/www/gradaeronaut.com/public/app/

Тогда текущий конфиг должен работать. Проверьте:

```bash
ls -la /var/www/gradaeronaut.com/public/app/register.php
```

Если файла нет, создайте симлинк:

```bash
ln -s /var/www/gradaeronaut.com/app /var/www/gradaeronaut.com/public/app
```

## Применение исправления

1. **Найти активный конфиг:**
   ```bash
   grep -l "server_name gradaeronaut.com" /etc/nginx/sites-enabled/*
   ```

2. **Создать бэкап:**
   ```bash
   sudo cp /etc/nginx/sites-enabled/sinbad.conf /etc/nginx/sites-enabled/sinbad.conf.backup
   ```

3. **Добавить location /app/ в конфиг** (см. Вариант 1 выше)

4. **Проверить синтаксис:**
   ```bash
   sudo nginx -t
   ```

5. **Перезагрузить nginx:**
   ```bash
   sudo systemctl reload nginx
   ```

6. **Проверить результат:**
   ```bash
   curl -i https://gradaeronaut.com/app/register.php
   ```

   Должен вернуться JSON ответ от PHP, а не HTML от nginx.

7. **Проверить логи:**
   ```bash
   tail -f /var/log/php*-fpm.log
   # или
   tail -f /var/log/nginx/error.log
   ```

   После запроса должны появиться строки `VERIFY_MAIL: register.php reached`

## Готовый исправленный конфиг

Исправленный конфиг сохранен в:
- `nginx/sites-available/sinbad.conf.fixed`

Сравните с текущим и примените изменения.



