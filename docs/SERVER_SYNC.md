# Server Synchronization Guide

## Обзор

Настроена двусторонняя синхронизация между локальным Mac и production сервером.

**Сервер**: `root@159.198.74.241:/var/www/gradaeronaut.com`  
**Git Remote**: `server`

## Доступные скрипты

### 1. Получение изменений с сервера

```bash
./tools/pull-from-server.sh
```

**Что делает:**
- Проверяет наличие незакоммиченных изменений (сохраняет в stash)
- Получает изменения с production сервера
- Выполняет merge
- Восстанавливает stash если был

**Когда использовать:**
- Перед началом работы
- После изменений на сервере другими разработчиками
- Для синхронизации с production

### 2. Отправка изменений на сервер

```bash
./tools/push-to-server.sh
```

**Что делает:**
- Проверяет отсутствие незакоммиченных изменений
- Проверяет, что локальная версия не отстаёт от сервера
- Показывает список коммитов для отправки
- Запрашивает подтверждение
- Отправляет изменения на сервер
- Опционально перезапускает Nginx и PHP-FPM

**Когда использовать:**
- После коммита изменений
- Для деплоя на production

### 3. Двусторонняя синхронизация

```bash
./tools/sync-with-server.sh
```

**Что делает:**
- Создаёт бэкап перед синхронизацией
- Получает изменения с сервера
- Предлагает отправить локальные изменения

**Когда использовать:**
- Для полной синхронизации
- Перед началом работы
- После длительного перерыва

## Типичные сценарии

### Начало рабочего дня

```bash
cd ~/Desktop/sinbad-portal

# Синхронизация с сервером
./tools/pull-from-server.sh

# Или полная двусторонняя синхронизация
./tools/sync-with-server.sh
```

### Деплой изменений на production

```bash
# 1. Убедитесь что все изменения закоммичены
git status

# 2. Если есть незакоммиченные изменения
git add .
git commit -m "Описание изменений"

# 3. Отправка на сервер
./tools/push-to-server.sh

# Скрипт спросит:
# - Подтверждение отправки
# - Перезапуск сервисов (Nginx, PHP-FPM)
```

### Работа с конфликтами

Если при pull возникли конфликты:

```bash
# 1. Скрипт остановится и покажет конфликтующие файлы
# 2. Разрешите конфликты вручную в редакторе
# 3. Добавьте разрешённые файлы
git add .

# 4. Завершите merge
git merge --continue

# 5. Проверьте результат
git status
```

### Откат изменений

Если нужно откатить последний push:

```bash
# На локальной машине
git reset --hard HEAD~1

# На сервере (через SSH)
ssh root@159.198.74.241 "cd /var/www/gradaeronaut.com && git fetch origin && git reset --hard origin/main"

# Или восстановить из бэкапа
rsync -av ~/SinbadBackups/YYYY-MM-DD_HH-MM-SS/ ~/Desktop/sinbad-portal/
```

## Git Remotes

Проект настроен с двумя remotes:

```bash
git remote -v

# Вывод:
# origin  https://github.com/GradAeronaut/sinbad-git-server.git (fetch)
# origin  https://github.com/GradAeronaut/sinbad-git-server.git (push)
```

**origin** - Единственный канонический репозиторий (sinbad-git-server.git)

## Ручные команды Git

### Получить изменения

```bash
# Fetch
git fetch origin main

# Посмотреть разницу
git log HEAD..origin/main

# Merge
git merge origin/main
```

### Отправить изменения

```bash
# Проверить что отправляем
git log origin/main..HEAD

# Push
git push origin main
```

### Работа с репозиторием

```bash
# Push в репозиторий
git push origin main

# Pull из репозитория
git pull origin main
```

## Автоматизация через Antigravity

Обновлён workflow `/sync-and-backup`:

```bash
/sync-and-backup
```

Теперь выполняет:
1. Синхронизацию с сервером
2. Создание бэкапа
3. Проверку статуса

## Безопасность

### SSH ключи

Убедитесь, что SSH ключи настроены:

```bash
# Проверка подключения
ssh root@159.198.74.241 "echo 'Connection OK'"

# Если нужно добавить ключ
ssh-copy-id root@159.198.74.241
```

### Бэкапы перед синхронизацией

Все скрипты синхронизации автоматически создают бэкапы:
- `sync-with-server.sh` - создаёт бэкап перед началом
- `pull-from-server.sh` - использует Git stash
- `push-to-server.sh` - проверяет состояние перед отправкой

## Troubleshooting

### Ошибка SSH подключения

```bash
# Проверить подключение
ssh -v root@159.198.74.241

# Проверить SSH ключи
ls -la ~/.ssh/

# Добавить ключ если нужно
ssh-keygen -t ed25519
ssh-copy-id root@159.198.74.241
```

### Git конфликты

```bash
# Посмотреть конфликтующие файлы
git status

# Отменить merge
git merge --abort

# Или разрешить конфликты и продолжить
# (отредактировать файлы)
git add .
git merge --continue
```

### Сервер отстал от локальной версии

```bash
# Принудительный push (ОСТОРОЖНО!)
git push server main --force

# Лучше: сначала pull, потом push
./tools/pull-from-server.sh
./tools/push-to-server.sh
```

### Проверка состояния сервера

```bash
# Статус Git на сервере
ssh root@159.198.74.241 "cd /var/www/gradaeronaut.com && git status"

# Последние коммиты
ssh root@159.198.74.241 "cd /var/www/gradaeronaut.com && git log --oneline -5"

# Текущая ветка и remotes
ssh root@159.198.74.241 "cd /var/www/gradaeronaut.com && git branch && git remote -v"
```

## Best Practices

1. **Всегда pull перед push**
   ```bash
   ./tools/pull-from-server.sh
   # работа с кодом
   git commit -m "..."
   ./tools/push-to-server.sh
   ```

2. **Используйте бэкапы**
   - Автоматические бэкапы создаются при каждом коммите
   - Перед крупными изменениями: `.git/hooks/backup-manager.sh create`

3. **Проверяйте перед push**
   ```bash
   git status
   git log server/main..HEAD
   ```

4. **Тестируйте локально**
   - Не отправляйте непроверенный код на production
   - Используйте ветку `dev` для экспериментов

5. **Перезапускайте сервисы после деплоя**
   - `push-to-server.sh` предложит это автоматически
   - Или вручную: `ssh root@159.198.74.241 "systemctl reload nginx"`

## Мониторинг

### Проверка синхронизации

```bash
# Локально
git log --oneline -5

# На сервере (должен синхронизироваться с origin)
ssh root@159.198.74.241 "cd /var/www/gradaeronaut.com && git fetch origin && git log --oneline -5"

# Должны совпадать после синхронизации
```

### Проверка бэкапов

```bash
# Список бэкапов
.git/hooks/backup-manager.sh list

# Последний бэкап
ls -lth ~/SinbadBackups/ | head -2
```
