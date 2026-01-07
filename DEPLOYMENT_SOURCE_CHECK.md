# Проверка источника деплоя на продакшн

**Дата:** 2025-12-29  
**Проблема:** Gate не работает на продакшн, возможно сервер деплоит другую ветку или есть auto-pull

**Сервер:** `root@159.198.74.241:/var/www/gradaeronaut.com`

---

## Шаг 1. Выяснить источник деплоя

### Команды для выполнения на сервере (SSH):

```bash
# Подключиться к серверу
ssh root@159.198.74.241

# Перейти в директорию проекта
cd /var/www/gradaeronaut.com

# ИЛИ использовать готовый скрипт (скопировать на сервер):
bash check_server_deployment.sh
```

### Ручная проверка:

```bash
# 1. Проверить git remote
git remote -v

# Ожидаемый результат:
# origin  https://github.com/GradAeronaut/sinbad-git-server.git (fetch)
# origin  https://github.com/GradAeronaut/sinbad-git-server.git (push)

# 2. Проверить текущую ветку
git branch --show-current

# Ожидаемый результат: main

# 3. Проверить текущий коммит
git rev-parse HEAD
git log --oneline -1

# 4. Сравнить с коммитом f42182b
git log --oneline f42182b..HEAD

# Если список пустой → HEAD находится на f42182b или раньше
# Если есть коммиты → HEAD новее f42182b

# 5. Проверить последний коммит в origin/main (или server/main)
# Если remote = origin:
git fetch origin
git log origin/main --oneline -1

# Если remote = server (или оба):
git fetch server
git log server/main --oneline -1

# 6. Проверить наличие gate в файле
head -15 shape-sinbad/index.php | grep -E "GATE_TEST|GATE_OK|session_start"
```

---

## Шаг 2. Принудительно доставить gate

### Вариант А: Если текущая ветка = main, но HEAD старый

```bash
# Если remote = origin:
git fetch origin
git reset --hard origin/main

# Если remote = server:
git fetch server
git reset --hard server/main

# Если оба remotes есть, использовать origin (источник правды):
git fetch origin
git reset --hard origin/main

# Проверить результат
git log --oneline -1
head -15 shape-sinbad/index.php
```

### Вариант Б: Если деплоится другая ветка

```bash
# 1. Проверить все ветки
git branch -a

# 2. Переключиться на нужную ветку (если нужно)
git checkout main

# 3. Обновить
git fetch origin
git reset --hard origin/main
```

### Вариант В: Если есть auto-pull/webhook, который откатывает код

#### Проверка cron:

```bash
# Проверить cron задачи пользователя
crontab -l

# Проверить системные cron задачи
cat /etc/crontab
ls -la /etc/cron.d/
```

#### Проверка systemd timers:

```bash
# Проверить все timers
systemctl list-timers --all

# Проверить конкретный timer (если найден)
systemctl status sinbad-portal-autopull.timer
```

#### Проверка webhook скриптов:

```bash
# Поиск webhook скриптов
find /var/www -name "*webhook*" -o -name "*autopull*" 2>/dev/null
find /home -name "*webhook*" -o -name "*autopull*" 2>/dev/null
find /opt -name "*webhook*" -o -name "*autopull*" 2>/dev/null
```

#### Если найден auto-pull скрипт:

```bash
# Проверить содержимое скрипта
cat /path/to/autopull-script.sh

# ВРЕМЕННО отключить (переименовать или закомментировать)
sudo mv /path/to/autopull-script.sh /path/to/autopull-script.sh.disabled

# ИЛИ закомментировать в cron
crontab -e
# Добавить # перед строкой с autopull
```

---

## Шаг 3. После исправления источника деплоя

### Обновить код на сервере:

```bash
cd /var/www/gradaeronaut.com

# Определить какой remote используется
REMOTE=$(git remote | head -1)

# Обновить код
git fetch $REMOTE
git reset --hard $REMOTE/main

# ИЛИ явно указать origin (GitHub - источник правды):
git fetch origin
git reset --hard origin/main

# Проверить, что gate код на месте
grep -n "GATE_TEST" shape-sinbad/index.php

# Проверить ответ сервера
curl -I https://gradaeronaut.com/shape-sinbad/
# Ожидаемый результат: HTTP/1.1 401 Unauthorized
```

### Если auto-pull отключен временно:

После подтверждения работы gate:
1. Убедиться, что gate работает корректно
2. Настроить auto-pull на правильную ветку/коммит
3. Включить auto-pull обратно

---

## Коммиты с gate (в хронологическом порядке):

1. `0ea2bc6` - Add authentication gate to Shape and remove hardcoded user data
2. `fb1db7c` - Add strict authentication gate with die for testing
3. `250683c` - Add GATE_TEST with 401 status for production verification

**Текущий HEAD (локально):** `6bb68bb` - Add production gate check instructions

**Коммит f42182b:** SSO tech debt cleanup (tokens TTL, test user removal) - **ДО добавления gate**

---

## Критично

Если сервер застрял на коммите `f42182b` или раньше, значит:
- Либо auto-pull/webhook откатывает код к старому коммиту
- Либо деплоится не та ветка
- Либо код не синхронизирован вручную
- Либо сервер использует remote `server` (прямое подключение), а код не был отправлен через него

**До исправления источника деплоя любые локальные правки бесполезны.**

---

## Важная информация о remotes

### Если на сервере есть remote `server` (прямое подключение):

Это означает, что код деплоится напрямую с локальной машины через `tools/push-to-server.sh`, а не через GitHub.

**Решение:**
1. Отправить код в репозиторий (если еще не отправлен):
   ```bash
   git push origin main
   ```

2. На сервере обновить с репозитория:
   ```bash
   ssh root@159.198.74.241
   cd /var/www/gradaeronaut.com
   git fetch origin
   git reset --hard origin/main
   ```

### На сервере должен быть только remote `origin`:

Код деплоится через sinbad-git-server.git:
```bash
# На сервере
cd /var/www/gradaeronaut.com
git fetch origin
git reset --hard origin/main
```

**ВАЖНО:** Убедитесь, что код сначала отправлен в GitHub (`git push origin main`), прежде чем обновлять сервер.

