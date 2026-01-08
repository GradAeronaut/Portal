# Статус автодеплоя

## Текущий статус

**Локально:** ✅ IDENTICAL с origin/main
- HEAD: `3e1a13f1aedeba39fe4833d6996d13ee0006bce4`
- Коммит: "Добавлены скрипты диагностики и исправления автодеплоя"

**Origin (GitHub):** ✅ IDENTICAL с локальной версией

**Сервер:** ⚠ Требуется проверка

## Скрипты диагностики

### 1. `check_versions.sh`
Простая проверка версий локально и с origin:
```bash
./check_versions.sh
```

### 2. `diagnose_and_fix_deploy.sh`
Полная диагностика и автоисправление (требует SSH доступ):
```bash
./diagnose_and_fix_deploy.sh
```

### 3. `deploy.sh`
Каноничный deploy скрипт для сервера. Должен быть размещен на сервере в:
- `/var/www/gradaeronaut.com/deploy.sh`

## Проверка на сервере

Для проверки идентичности версий на сервере выполните:

```bash
# На сервере
cd /var/www/gradaeronaut.com
git rev-parse HEAD
# Должен быть: 3e1a13f1aedeba39fe4833d6996d13ee0006bce4
```

Если версии различаются:

```bash
# На сервере
cd /var/www/gradaeronaut.com
git fetch origin
git reset --hard origin/main
```

## Настройка автодеплоя

### GitHub Webhook

1. Перейдите в настройки репозитория: `Settings > Webhooks`
2. Добавьте webhook с URL: `https://gradaeronaut.com/webhook/deploy` (или соответствующий endpoint)
3. Content type: `application/json`
4. Secret: (настройте на сервере)
5. Events: `push` (только для ветки `main`)

### Deploy скрипт на сервере

Скопируйте `deploy.sh` на сервер:

```bash
# С локальной машины
scp deploy.sh user@gradaeronaut.com:/var/www/gradaeronaut.com/
ssh user@gradaeronaut.com "chmod +x /var/www/gradaeronaut.com/deploy.sh"
```

### Webhook handler (PHP пример)

Создайте на сервере `/var/www/gradaeronaut.com/public/webhook/deploy.php`:

```php
<?php
// Проверка secret (рекомендуется)
$secret = 'your-webhook-secret';
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
// ... проверка подписи ...

// Выполнение deploy скрипта
$deploy_script = '/var/www/gradaeronaut.com/deploy.sh';
exec("bash $deploy_script 2>&1", $output, $return_code);

http_response_code($return_code === 0 ? 200 : 500);
echo json_encode(['status' => $return_code === 0 ? 'success' : 'error', 'output' => $output]);
```

## Каноничный deploy процесс

Deploy скрипт должен выполнять **ТОЛЬКО**:

1. `git fetch origin` - получение обновлений
2. `git reset --hard origin/main` - жесткий сброс на origin/main

**НЕ использовать:**
- ❌ `git pull` (может вызвать конфликты)
- ❌ `git merge` (не нужен при reset --hard)

## Логирование

Deploy скрипт логирует в `/var/log/portal-deploy.log`

Просмотр логов:
```bash
tail -f /var/log/portal-deploy.log
```

