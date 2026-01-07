# Финальная диагностика деплоя (Шаги 1-2) - READ-ONLY

**Дата:** 2025-12-29  
**Цель:** Определить источник деплоя и причину отсутствия gate кода (БЕЗ изменений)

**ВАЖНО:** Только диагностика, никаких изменений на сервере!

---

## Шаг 1. Запуск проверки на сервере (READ-ONLY)

### Вариант А: Автоматическая диагностика (рекомендуется) - READ-ONLY

```bash
# Подключиться к серверу
ssh root@159.198.74.241

# Перейти в директорию проекта
cd /var/www/gradaeronaut.com

# Скопировать скрипт диагностики на сервер (с локальной машины):
# scp diagnose_deployment_readonly.sh root@159.198.74.241:/tmp/

# Выполнить диагностику (READ-ONLY, без изменений)
bash /tmp/diagnose_deployment_readonly.sh > /tmp/deployment_diagnosis_output.txt 2>&1

# Просмотреть результат
cat /tmp/deployment_diagnosis_output.txt

# Скопировать результат на локальную машину для анализа:
# scp root@159.198.74.241:/tmp/deployment_diagnosis_output.txt ./
```

**ВНИМАНИЕ:** Скрипт `diagnose_deployment_readonly.sh` НЕ делает никаких изменений:
- ❌ НЕ выполняет `git reset`
- ❌ НЕ выполняет `git pull`
- ❌ НЕ перезапускает сервисы
- ✅ Только читает информацию

### Вариант Б: Ручная проверка

```bash
ssh root@159.198.74.241
cd /var/www/gradaeronaut.com

# 1. Проверить remotes
echo "=== Git Remotes ==="
git remote -v

# 2. Проверить текущую ветку
echo "=== Текущая ветка ==="
git branch --show-current

# 3. Проверить текущий коммит
echo "=== Текущий коммит (HEAD) ==="
git rev-parse HEAD
git log --oneline -1

# 4. Проверить коммит f42182b (до gate)
echo "=== Проверка коммита f42182b ==="
git log f42182b --oneline -1 2>/dev/null || echo "Коммит f42182b не найден"
if [ "$(git rev-parse HEAD)" = "$(git rev-parse f42182b 2>/dev/null)" ]; then
    echo "❌ HEAD находится на f42182b (до добавления gate)"
else
    echo "✓ HEAD не на f42182b"
fi

# 5. Проверить наличие gate кода
echo "=== Проверка gate кода ==="
if [ -f "shape-sinbad/index.php" ]; then
    if grep -q "GATE_TEST" shape-sinbad/index.php 2>/dev/null; then
        echo "✓ Файл содержит GATE_TEST"
    elif grep -q "session_start" shape-sinbad/index.php 2>/dev/null; then
        echo "⚠ Файл содержит session_start, но нет GATE_TEST"
        echo "Первые 15 строк:"
        head -15 shape-sinbad/index.php
    else
        echo "❌ Файл НЕ содержит gate код"
    fi
else
    echo "❌ Файл shape-sinbad/index.php не найден"
fi

# 6. Проверить последний коммит в origin/main (если есть)
if git remote | grep -q "^origin$"; then
    echo "=== Последний коммит в origin/main ==="
    git fetch origin 2>/dev/null || echo "Не удалось выполнить git fetch origin"
    git log origin/main --oneline -1 2>/dev/null || echo "Ветка origin/main не найдена"
fi

# 7. Проверить последний коммит в server/main (если есть)
if git remote | grep -q "^server$"; then
    echo "=== Последний коммит в server/main ==="
    git fetch server 2>/dev/null || echo "Не удалось выполнить git fetch server"
    git log server/main --oneline -1 2>/dev/null || echo "Ветка server/main не найдена"
fi

# 8. Проверить cron/webhook/auto-pull
echo "=== Проверка автоматического деплоя ==="
echo "Cron задачи:"
crontab -l 2>/dev/null | grep -i "git\|deploy\|pull" || echo "  Нет cron задач с git/deploy/pull"

echo "Systemd timers:"
systemctl list-timers --all 2>/dev/null | grep -i "sinbad\|portal\|deploy" || echo "  Нет systemd timers"
```

### Что зафиксировать:

После выполнения проверки зафиксируйте:

1. **Какой remote используется:**
   - `origin` (GitHub) — ✅ предпочтительно
   - `server` (прямое подключение) — ⚠️ требует синхронизации
   - Оба — определить, какой является источником истины

2. **Текущий коммит на сервере:**
   - Если это `f42182b` или раньше → требуется обновление
   - Если это `250683c` или новее → код должен быть актуальным

3. **Наличие gate кода:**
   - Содержит `GATE_TEST` → ✅ код актуален
   - Содержит `GATE_OK` → ⚠️ старая версия
   - Не содержит gate код → ❌ требуется обновление

4. **Автоматический деплой:**
   - Если найден auto-pull/webhook → проверить, не откатывает ли он код к `f42182b`

---

## Шаг 2. Анализ результатов и отчёт

После выполнения диагностики:

1. **Заполнить отчёт:** `DEPLOYMENT_DIAGNOSIS_REPORT.md`
2. **Ответить на ключевые вопросы:**
   - Какой remote используется для деплоя?
   - Почему сервер остаётся на f42182b?
   - Подтверждение, что код с gate не попадает на сервер?

---

## Шаг 3. Приведение источника истины в норму (ПОСЛЕ диагностики)

### Сценарий А: Remote = `origin` (GitHub)

Если на сервере есть remote `origin` и он указывает на GitHub:

```bash
# Обновить код с GitHub
cd /var/www/gradaeronaut.com
git fetch origin
git reset --hard origin/main

# Проверить результат
git log --oneline -1
grep -n "GATE_TEST" shape-sinbad/index.php

# Проверить ответ сервера
curl -I https://gradaeronaut.com/shape-sinbad/
# Ожидается: HTTP/1.1 401 Unauthorized
```

**ВАЖНО:** Перед обновлением на сервере убедитесь, что код отправлен в GitHub:
```bash
# Локально
git push origin main
```

### Сценарий Б: Remote = `server` (прямое подключение)

Если на сервере только remote `server`:

**Вариант 1: Добавить remote `origin` (рекомендуется)**

```bash
# На сервере
cd /var/www/gradaeronaut.com

# Добавить remote origin (sinbad-git-server.git)
git remote add origin https://github.com/GradAeronaut/sinbad-git-server.git
# ИЛИ через SSH:
# git remote add origin git@github.com:GradAeronaut/sinbad-git-server.git

# Проверить remotes
git remote -v

# Обновить код с GitHub
git fetch origin
git reset --hard origin/main
```

**Вариант 2: Отправить код через `server` remote**

```bash
# Локально
./tools/push-to-server.sh
```

### Сценарий В: Автоматическое исправление

Использовать скрипт `fix_server_deployment.sh`:

```bash
# На сервере
cd /var/www/gradaeronaut.com

# Скопировать скрипт (с локальной машины):
# scp fix_server_deployment.sh root@159.198.74.241:/tmp/

# Выполнить исправление
bash /tmp/fix_server_deployment.sh
```

Скрипт автоматически:
- Определит используемый remote
- Проверит текущий коммит
- Обновит код до последней версии `origin/main` (если доступен)
- Проверит наличие gate кода

---

## Шаг 3. Проверка результата

После исправления источника деплоя:

```bash
# 1. Проверить текущий коммит
cd /var/www/gradaeronaut.com
git log --oneline -1
# Должен быть: 250683c или новее (с gate кодом)

# 2. Проверить gate код
grep -n "GATE_TEST" shape-sinbad/index.php
# Должна быть строка с GATE_TEST

# 3. Проверить ответ сервера (БЕЗ сессии)
curl -I https://gradaeronaut.com/shape-sinbad/
# Ожидается:
# HTTP/1.1 401 Unauthorized
# (не HTTP 200 OK)

# 4. Опционально: перезапустить PHP-FPM
sudo systemctl restart php8.3-fpm
```

---

## Критично

- **До исправления источника деплоя любые локальные правки бесполезны**
- **Если на сервере используется remote `server`, код должен быть отправлен через `./tools/push-to-server.sh`**
- **Если на сервере используется remote `origin`, код должен быть отправлен в GitHub (`git push origin main`)**
- **Убедитесь, что код отправлен в GitHub перед обновлением на сервере**

---

## Коммиты с gate (в хронологическом порядке):

1. `0ea2bc6` - Add authentication gate to Shape and remove hardcoded user data
2. `fb1db7c` - Add strict authentication gate with die for testing
3. `250683c` - Add GATE_TEST with 401 status for production verification
4. `fd374ff` - Add server deployment source check script and instructions
5. `9c25b65` - Update deployment check script with remote detection
6. `9e191a3` - Add critical info about server remote types and GitHub sync requirement

**Коммит f42182b:** SSO tech debt cleanup (tokens TTL, test user removal) - **ДО добавления gate**

**Текущий HEAD (локально):** `9e191a3` или новее

