# ПРОВЕРКА СИНХРОНИЗАЦИИ СЕРВЕРА

## ПРОБЛЕМА
DevTools показывает старую версию CSS (bottom: 20px вместо 15px).
Изменения не закоммичены в Git → сервер отдает старую версию.

## ТЕКУЩЕЕ СОСТОЯНИЕ ЛОКАЛЬНОГО РЕПОЗИТОРИЯ

**Незакоммиченные изменения:**
- shape-sinbad-new/style.css
- shape-sinbad-new/index.php
- app/avatar/view_avatar.php
- menu/menu.css
- shape-sinbad/style.css

**Последний коммит:** 5ef5e89 Hard align containers...

## ЧТО ПРОВЕРИТЬ НА СЕРВЕРЕ

### 1. Проверить состояние Git на сервере:

```bash
cd /var/www/gradaeronaut.com
git status
git log -1 --oneline
```

**Ожидаемый результат:** 
- Если сервер синхронизирован, последний коммит должен быть `5ef5e89`
- Если есть незакоммиченные изменения, они НЕ будут на сервере

### 2. Проверить файл на сервере:

```bash
grep -n "bottom:" /var/www/gradaeronaut.com/shape-sinbad-new/style.css | head -5
```

**Ожидаемый результат:**
- Если видите `bottom: 20px` → сервер на старой версии
- Если видите `bottom: 15px !important` → сервер обновлен

### 3. Проверить контрольный маркер:

В браузере на странице должен появиться красный маркер **"SERVER-OK-v2"** в правом нижнем углу.

**Если маркер НЕ появился:**
- Сервер отдает старую версию CSS
- Нужно закоммитить и запушить изменения

**Если маркер появился:**
- Сервер обновлен, но возможно кэш браузера

### 4. Если нужно обновить сервер:

```bash
cd /var/www/gradaeronaut.com
git fetch origin
git reset --hard origin/main
```

### 5. Перезапустить сервисы (если нужно):

```bash
sudo systemctl restart php-fpm
sudo systemctl reload nginx
```

## СЛЕДУЮЩИЕ ШАГИ

1. Закоммитить изменения локально
2. Запушить в origin/main
3. На сервере выполнить `git pull` или `git reset --hard origin/main`
4. Проверить маркер в браузере
