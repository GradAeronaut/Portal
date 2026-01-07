# Настройка GitHub для продакшн-сервера

## Текущее состояние

✅ **Репозиторий инициализирован**: `/var/www/gradaeronaut.com`  
✅ **Основная ветка**: `main`  
✅ **Последний commit**: `e020412 - Major update: XenForo forum integration + backup infrastructure`  
✅ **Рабочая директория чистая**: `nothing to commit, working tree clean`

## Что уже готово

1. **Git-репозиторий настроен**
   - Все файлы добавлены в git
   - Чувствительные данные исключены через `.gitignore`
   - Форум XenForo включен в репозиторий (без internal_data и data)
   - Создан полноценный commit с описанием изменений

2. **Backup инфраструктура**
   - Директория `/var/backups/xenforo/` создана
   - Скрипт автоматического бэкапа готов
   - Cron-задача настроена (ежедневно в 2:00 UTC)
   - Скрипт передачи бэкапов на Mac подготовлен

3. **Документация**
   - `BACKUP_AND_GIT_SETUP.md` - полное руководство по бэкапам
   - `GITHUB_SETUP.md` (этот файл) - настройка GitHub
   - Все SSO и OAuth гайды обновлены

## Шаги для подключения к GitHub

### 1. Настройка Git пользователя (на сервере)

```bash
cd /var/www/gradaeronaut.com

# Настроить имя и email
git config user.name "Your Name"
git config user.email "your.email@example.com"

# Проверить настройки
git config --list | grep user
```

### 2. Создание репозитория на GitHub

**Вариант A: Через веб-интерфейс GitHub**

1. Зайти на https://github.com
2. Нажать "New repository"
3. Имя репозитория: `gradaeronaut.com` (или другое на ваш выбор)
4. Установить: Private repository (рекомендуется)
5. **НЕ** добавлять README, .gitignore, или license (они уже есть локально)
6. Нажать "Create repository"

**Вариант B: Через GitHub CLI (если установлен)**

```bash
# Создать приватный репозиторий
gh repo create gradaeronaut.com --private --source=. --remote=origin

# Или публичный
gh repo create gradaeronaut.com --public --source=. --remote=origin
```

### 3. Настройка SSH ключа для GitHub (рекомендуется)

```bash
# Проверить существующие SSH ключи
ls -la ~/.ssh/id_*.pub

# Если ключа нет, создать новый
ssh-keygen -t ed25519 -C "your.email@example.com"

# Скопировать публичный ключ
cat ~/.ssh/id_ed25519.pub

# Добавить ключ на GitHub:
# 1. Зайти в GitHub → Settings → SSH and GPG keys
# 2. Нажать "New SSH key"
# 3. Вставить содержимое публичного ключа
# 4. Сохранить

# Проверить соединение
ssh -T git@github.com
```

### 4. Добавление remote репозитория

```bash
cd /var/www/gradaeronaut.com

# Добавить remote (sinbad-git-server.git)
git remote add origin https://github.com/GradAeronaut/sinbad-git-server.git

# Или через SSH:
# git remote add origin git@github.com:GradAeronaut/sinbad-git-server.git

# Проверить remote
git remote -v
```

### 5. Первый push на GitHub

```bash
cd /var/www/gradaeronaut.com

# Push в main ветку
git push -u origin main

# Проверить статус
git status
git log --oneline -3
```

## Рабочий процесс после настройки GitHub

### Получение изменений с GitHub (deployment)

```bash
cd /var/www/gradaeronaut.com

# Проверить текущий статус
git status

# Если есть локальные изменения, сохранить их
git stash

# Получить изменения из GitHub
git pull origin main

# Если были stash'нуты изменения, применить их обратно
git stash pop

# Перезапустить сервисы если нужно
sudo systemctl reload nginx
sudo systemctl restart php8.1-fpm
```

### Отправка изменений на GitHub (если редактируете на сервере)

```bash
cd /var/www/gradaeronaut.com

# Проверить изменения
git status
git diff

# Добавить измененные файлы
git add .

# Создать commit
git commit -m "Описание изменений"

# Отправить на GitHub
git push origin main
```

## Проверка готовности к git pull

### Чек-лист готовности

- [x] Git репозиторий инициализирован
- [x] Основная ветка `main` существует
- [x] Все файлы закоммичены
- [x] Рабочая директория чистая (no uncommitted changes)
- [x] `.gitignore` правильно настроен
- [ ] Git пользователь настроен (`git config user.name` и `user.email`)
- [ ] Remote `origin` добавлен
- [ ] SSH ключ добавлен на GitHub (для SSH) или токен настроен (для HTTPS)
- [ ] Выполнен первый `git push -u origin main`

### Команды для проверки

```bash
cd /var/www/gradaeronaut.com

# Проверить текущую ветку
git branch

# Проверить статус (должно быть "nothing to commit, working tree clean")
git status

# Проверить remote (должен быть origin)
git remote -v

# Проверить последние коммиты
git log --oneline -5

# Проверить Git конфигурацию
git config --list | grep -E "(user|remote)"

# Тест соединения с GitHub (для SSH)
ssh -T git@github.com
```

### Что делать если git pull выдает ошибку

**Ошибка: "no tracking information"**

```bash
# Установить upstream для ветки main
git branch --set-upstream-to=origin/main main
```

**Ошибка: "divergent branches"**

```bash
# Вариант 1: Rebase (рекомендуется для чистой истории)
git pull --rebase origin main

# Вариант 2: Merge
git pull --no-rebase origin main
```

**Ошибка: "Permission denied (publickey)"**

```bash
# Проверить SSH ключ
ssh-add -l

# Добавить ключ в SSH agent
ssh-add ~/.ssh/id_ed25519

# Или использовать SSH вместо HTTPS
git remote set-url origin git@github.com:GradAeronaut/sinbad-git-server.git
```

## Рекомендации по безопасности

### Что НЕ должно попадать в Git

✅ **Уже исключено через `.gitignore`:**
- `forum/internal_data/` - сессии, кэш форума
- `forum/data/` - загрузки пользователей
- `xenforo_*.zip` - архивы дистрибутива
- `*.sql.gz`, `*.sql.bak` - дампы БД
- `config/google_oauth.php` - OAuth credentials

⚠️ **Проверить перед push:**

```bash
# Проверить что конфиденциальные данные не попали в commit
git log -p | grep -i "password\|secret\|key\|token" --color

# Если нашли конфиденциальные данные, удалить из истории:
# (ВНИМАНИЕ: это переписывает историю!)
git filter-branch --force --index-filter \
  "git rm --cached --ignore-unmatch path/to/sensitive/file" \
  --prune-empty --tag-name-filter cat -- --all
```

### Работа с конфиденциальными файлами

Для конфиденциальных файлов, которые нужны на сервере, но не должны быть в Git:

1. Создать `.example` версию файла:
   ```bash
   cp config/google_oauth.php config/google_oauth.php.example
   # Заменить реальные credentials на плейсхолдеры
   ```

2. Добавить `.example` в git:
   ```bash
   git add config/google_oauth.php.example
   git commit -m "Add example OAuth config"
   ```

3. Реальный файл уже исключен через `.gitignore`

## Автоматизация deployment (опционально)

### Простой deployment скрипт

```bash
# Создать /var/www/gradaeronaut.com/deploy.sh

#!/bin/bash
cd /var/www/gradaeronaut.com

echo "Starting deployment..."

# Backup текущего состояния
git stash

# Получить изменения
git pull origin main

# Применить stashed изменения если были
git stash pop 2>/dev/null

# Перезапустить сервисы
sudo systemctl reload nginx
sudo systemctl restart php8.1-fpm

echo "Deployment completed!"
```

### GitHub Actions для автоматического деплоя (advanced)

Создать `.github/workflows/deploy.yml` в репозитории для автоматического деплоя при push в main:

```yaml
name: Deploy to Production

on:
  push:
    branches: [ main ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - name: Deploy to server
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.SERVER_HOST }}
          username: ${{ secrets.SERVER_USER }}
          key: ${{ secrets.SERVER_SSH_KEY }}
          script: |
            cd /var/www/gradaeronaut.com
            git pull origin main
            sudo systemctl reload nginx
```

## Мониторинг и maintenance

### Регулярные проверки

```bash
# Еженедельно проверять размер репозитория
cd /var/www/gradaeronaut.com
du -sh .git/

# Если репозиторий слишком большой, очистить:
git gc --aggressive --prune=now

# Проверить что .gitignore работает
git status --ignored
```

### Логирование Git операций

```bash
# Посмотреть кто и когда делал pull/push
git reflog

# История изменений с датами
git log --pretty=format:"%h - %an, %ar : %s"
```

## Контакты и troubleshooting

При проблемах с Git:

1. Проверить логи: `git status`, `git log`
2. Проверить remote: `git remote -v`
3. Проверить ветки: `git branch -a`
4. Проверить конфигурацию: `git config --list`

При проблемах с доступом к GitHub:
1. Проверить SSH: `ssh -T git@github.com`
2. Проверить права доступа к репозиторию на GitHub
3. Проверить что SSH ключ добавлен в GitHub Settings

---

**Дата создания:** 5 декабря 2025  
**Версия:** 1.0  
**Статус:** Production Ready



