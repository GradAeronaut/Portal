# Sinbad Portal - Git Workflow
<!-- Auto-push hook enabled -->

## Структура веток

### main
- **Назначение**: Production-ветка (серверная версия)
- **Источник**: `/var/www/gradaeronaut.com` на сервере
- **Правило**: только стабильный, протестированный код

### dev
- **Назначение**: Рабочая ветка для разработки на Mac
- **Использование**: все изменения, рефакторинг, эксперименты
- **Merge в main**: только после тестирования

## Автоматизация

### Auto-Pull (перед операциями)

Перед каждой операцией Antigravity выполняет:

```bash
.git/hooks/auto-pull.sh
```

Это обеспечивает:
- Синхронизацию с удалённым репозиторием
- Предотвращение конфликтов
- Актуальность локальной копии

### Auto-Backup (после операций)

После каждого commit или merge автоматически создаётся бэкап:

```bash
git commit -m "Update"
# → автоматически: backup-manager.sh create
```

### Pre-Push Validation

Перед push проверяется:
- Отсутствие незакоммиченных изменений
- Чистота рабочей директории
- Наличие неотслеживаемых файлов

## Типичные операции

### Начало работы

```bash
cd ~/Desktop/sinbad-portal

# Автоматическое обновление
.git/hooks/auto-pull.sh

# Или вручную
git pull origin dev
```

### Коммит изменений

```bash
# Проверить статус
git status

# Добавить файлы
git add .

# Коммит (автоматически создаст бэкап)
git commit -m "Описание изменений"
```

### Отправка на сервер

```bash
# Push (автоматически проверит чистоту)
git push origin dev
```

### Merge dev → main

```bash
# Переключиться на main
git checkout main

# Обновить main
git pull origin main

# Merge dev
git merge dev

# Push на сервер
git push origin main
```

## Правила работы Antigravity

### 1. Всегда начинать с auto-pull

```bash
.git/hooks/auto-pull.sh
```

### 2. После изменений - commit

```bash
git add .
git commit -m "Antigravity: [описание изменений]"
```

### 3. Предлагать push пользователю

Antigravity не делает push автоматически, а предлагает:

```
Изменения закоммичены. Выполнить push?
git push origin dev
```

## Крупные рефакторинги

### Подготовка

```bash
# 1. Создать feature-ветку
git checkout -b refactor/module-name

# 2. Создать бэкап вручную
.git/hooks/backup-manager.sh create

# 3. Выполнить рефакторинг
# ...

# 4. Коммит по частям
git add app/module1/
git commit -m "Refactor: module1"

git add app/module2/
git commit -m "Refactor: module2"
```

### Тестирование

```bash
# Запустить тесты
# Проверить работоспособность

# Если всё OK - merge в dev
git checkout dev
git merge refactor/module-name
```

### Откат при проблемах

```bash
# Вернуться к предыдущему коммиту
git reset --hard HEAD~1

# Или восстановить из бэкапа
rsync -av ~/SinbadBackups/YYYY-MM-DD_HH-MM-SS/ ~/Desktop/sinbad-portal/
```

## Синхронизация с сервером

### Первоначальная настройка

```bash
# Добавить сервер как remote (если нужно)
git remote add server user@gradaeronaut.com:/var/www/gradaeronaut.com

# Проверить remotes
git remote -v
```

### Получение изменений с сервера

```bash
# Fetch с сервера
git fetch server main

# Merge изменений
git merge server/main
```

### Отправка на сервер

```bash
# Push в GitHub
git push origin main

# Push на сервер (если настроен прямой доступ)
git push server main
```

## Конфликты

### Разрешение конфликтов

```bash
# При pull возник конфликт
git pull origin dev

# Посмотреть конфликтующие файлы
git status

# Отредактировать файлы, удалить маркеры <<<<< ===== >>>>>

# Добавить разрешённые файлы
git add .

# Завершить merge
git commit -m "Resolve merge conflicts"
```

### Отмена merge

```bash
# Если merge пошёл не так
git merge --abort
```

## Best Practices

1. **Частые коммиты**: лучше много мелких, чем один большой
2. **Понятные сообщения**: описывайте ЧТО и ЗАЧЕМ изменили
3. **Тестируйте перед push**: убедитесь, что код работает
4. **Используйте ветки**: для экспериментов создавайте отдельные ветки
5. **Регулярный pull**: синхронизируйтесь с сервером минимум раз в день

## Workflows для Antigravity

См. `.agent/workflows/` для готовых процедур:
- `sync-and-backup.md` - синхронизация и бэкап
- `safe-refactor.md` - безопасный рефакторинг

## Troubleshooting

### Забыл сделать pull перед изменениями

```bash
# Спрятать изменения
git stash

# Обновиться
git pull origin dev

# Вернуть изменения
git stash pop
```

### Случайно закоммитил в main вместо dev

```bash
# Отменить последний коммит (сохранив изменения)
git reset --soft HEAD~1

# Переключиться на dev
git checkout dev

# Закоммитить снова
git commit -m "..."
```

### Нужно откатить последний коммит

```bash
# Мягкий откат (сохранить изменения)
git reset --soft HEAD~1

# Жёсткий откат (удалить изменения)
git reset --hard HEAD~1

# Или восстановить из бэкапа
```
