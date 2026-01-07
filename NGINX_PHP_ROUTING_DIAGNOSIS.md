# Диагностика маршрутизации PHP для /app/

## Проблема
Запросы к `/app/register.php` и `/app/resend_verification.php` не доходят до PHP, логи `VERIFY_MAIL` не появляются.

## Шаг 1: Проверка HTTP-ответов

Выполните на сервере:

```bash
curl -i https://gradaeronaut.com/app/register.php
curl -i https://gradaeronaut.com/app/resend_verification.php
```

**Ожидаемые результаты:**
- ❌ **Плохо**: HTTP 404, HTML от nginx, или редирект
- ✅ **Хорошо**: HTTP 200, `Content-Type: application/json`, ответ от PHP

## Шаг 2: Проверка nginx конфигурации

### 2.1. Найти активный конфиг:

```bash
grep -R "server_name gradaeronaut.com" /etc/nginx/
```

### 2.2. Проверить наличие location для PHP:

```bash
grep -R "location ~ \.php$" /etc/nginx/sites-enabled/
```

### 2.3. Проверить location для /app:

```bash
grep -R "location /app" /etc/nginx/
```

### 2.4. Проверить root директиву:

В найденном конфиге проверьте:
- `root /var/www/gradaeronaut.com/public;` - это означает, что nginx ищет файлы в `/public/`
- Если файлы находятся в `/var/www/gradaeronaut.com/app/`, а не в `/var/www/gradaeronaut.com/public/app/`, то nginx их не найдет

## Шаг 3: Проверка физических путей

```bash
# Проверка структуры на сервере
ls -la /var/www/gradaeronaut.com/app/register.php
ls -la /var/www/gradaeronaut.com/public/app/register.php
ls -la /var/www/gradaeronaut.com/public/index.php
```

## Возможные проблемы и решения

### Проблема 1: Файлы не в public/

**Симптом:** root = `/var/www/gradaeronaut.com/public`, но файлы в `/var/www/gradaeronaut.com/app/`

**Решение 1:** Добавить location для /app/ в nginx конфиг:

```nginx
# Добавить ПЕРЕД location ~ \.php$
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

**Решение 2:** Изменить root на корень проекта:

```nginx
root /var/www/gradaeronaut.com;
```

Но тогда нужно обновить все пути (forum, assets и т.д.)

**Решение 3:** Создать симлинки или скопировать файлы:

```bash
# Вариант A: Симлинк
ln -s /var/www/gradaeronaut.com/app /var/www/gradaeronaut.com/public/app

# Вариант B: Копирование (не рекомендуется, дублирование)
cp -r /var/www/gradaeronaut.com/app /var/www/gradaeronaut.com/public/
```

### Проблема 2: location ~ \.php$ не обрабатывает /app/

**Симптом:** location ~ \.php$ есть, но не срабатывает для /app/

**Решение:** Проверить порядок location блоков. Более специфичные должны быть ПЕРЕД общими:

```nginx
# Сначала специфичные
location /app/ {
    # ...
}

# Потом общие
location ~ \.php$ {
    # ...
}
```

### Проблема 3: PHP-FPM не работает

**Проверка:**

```bash
# Проверка сокета
ls -la /run/php/php8.3-fpm.sock
# или
ls -la /run/php/php*-fpm.sock

# Проверка статуса
systemctl status php8.3-fpm
# или
systemctl status php-fpm
```

## Быстрый тест после исправления

После изменения nginx конфига:

```bash
# Проверка синтаксиса
nginx -t

# Перезагрузка nginx
systemctl reload nginx

# Тест запроса
curl -i https://gradaeronaut.com/app/register.php
```

**Критерий успеха:**
- HTTP 200
- `Content-Type: application/json`
- В ответе JSON (не HTML)
- В error_log появляются строки `VERIFY_MAIL: register.php reached`

## Автоматическая диагностика

Используйте скрипт:

```bash
./check_nginx_php_routing.sh
```

Он выполнит все проверки автоматически.



