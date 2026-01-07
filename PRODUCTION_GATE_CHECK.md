# Инструкция: Проверка и синхронизация PHP-gate на продакшн

**Дата:** 2025-12-29  
**Цель:** Убедиться, что PHP-gate выполняется на продакшн сервере

---

## Шаг 1. Синхронизация продакшна

### Команды для выполнения на сервере (SSH):

```bash
# Подключиться к серверу
ssh user@your-server

# Перейти в директорию проекта
cd /var/www/gradaeronaut.com

# Проверить текущий коммит
git rev-parse HEAD

# Сравнить с ожидаемым коммитом
# Ожидаемый коммит с gate: fb1db7c или более новый
# Если не совпадает → синхронизировать:

# Получить последние изменения
git fetch origin

# Принудительно обновить до последней версии main
git reset --hard origin/main

# Проверить текущий коммит после обновления
git rev-parse HEAD
git log --oneline -3
```

### Ожидаемый результат:

После `git reset --hard origin/main` последний коммит должен быть:
- `9c8f987` (или более новый) - с отчетом
- Или коммит с тестовым кодом `GATE_TEST`

---

## Шаг 2. Подтверждение выполнения PHP-gate

### Текущее состояние файла:

В `shape-sinbad/index.php` добавлен тестовый код:
```php
<?php
session_start();

// ТЕСТОВЫЙ КОД: подтверждение выполнения PHP-gate (временно)
http_response_code(401);
die('GATE_TEST');
```

### Проверка на сервере:

```bash
# Проверить ответ сервера
curl -I https://gradaeronaut.com/shape-sinbad/
```

### Ожидаемый результат:

**ДОЛЖНО БЫТЬ:**
```
HTTP/1.1 401 Unauthorized
...
```

**ЕСЛИ НЕ 401:**
- Возможные причины:
  1. Файл не обновлён на сервере
  2. Выполняется другой файл/путь
  3. Кеш PHP/OPcache
  4. Неправильный путь к файлу

### Действия если не 401:

1. Проверить путь к файлу на сервере:
```bash
ls -la /var/www/gradaeronaut.com/shape-sinbad/index.php
cat /var/www/gradaeronaut.com/shape-sinbad/index.php | head -15
```

2. Проверить, что файл содержит тестовый код:
```bash
grep -n "GATE_TEST" /var/www/gradaeronaut.com/shape-sinbad/index.php
```

3. Очистить PHP кеш (если используется OPcache):
```bash
# Перезапустить PHP-FPM
sudo systemctl restart php8.3-fpm
# или
sudo systemctl restart php-fpm
```

4. Проверить структуру директорий:
```bash
# Проверить document root
ls -la /var/www/gradaeronaut.com/public/shape-sinbad/
# ИЛИ
ls -la /var/www/gradaeronaut.com/shape-sinbad/
```

---

## Шаг 3. После подтверждения

Когда получите HTTP 401 с `GATE_TEST`:

1. Убедитесь, что это правильный файл
2. Верните нормальный gate код (замените тестовый код на проверку авторизации)
3. Проверьте, что без авторизации возвращается 302 редирект



