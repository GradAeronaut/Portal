# Настройка Git Workflow для синхронизации

## Обзор

Используется стандартный Git workflow для синхронизации между сервером и Mac:

1. **Сервер** автоматически делает `git push origin main` при каждом коммите (post-commit hook)
2. **Mac** получает изменения через `git pull origin main`

## Настройка на сервере

### 1. Настройка origin remote (если еще не настроен)

```bash
cd /var/www/gradaeronaut.com

# Добавить remote (sinbad-git-server.git)
git remote add origin https://github.com/GradAeronaut/sinbad-git-server.git

# Или через SSH:
# git remote add origin git@github.com:GradAeronaut/sinbad-git-server.git

# Проверить
git remote -v
```

### 2. Настройка SSH ключа для GitHub (рекомендуется)

```bash
# Проверить существующие ключи
ls -la ~/.ssh/id_*.pub

# Если ключа нет, создать новый
ssh-keygen -t ed25519 -C "your.email@example.com"

# Скопировать публичный ключ
cat ~/.ssh/id_ed25519.pub

# Добавить ключ на GitHub:
# 1. GitHub → Settings → SSH and GPG keys → New SSH key
# 2. Вставить содержимое публичного ключа
# 3. Сохранить

# Проверить соединение (если используется SSH)
ssh -T git@github.com
```

### 3. Первый push на GitHub

```bash
cd /var/www/gradaeronaut.com
git push -u origin main
```

### 4. Проверка post-commit hook

Hook уже настроен и автоматически делает push при каждом коммите:

```bash
cat .git/hooks/post-commit
```

Должен содержать:
```bash
#!/bin/bash
# Push to origin main (Git workflow)
if git remote | grep -q "^origin$"; then
    git push origin main 2>/dev/null || true
fi
```

## Настройка на Mac

### 1. Клонировать репозиторий (если еще не клонирован)

```bash
cd ~/Desktop
git clone https://github.com/GradAeronaut/sinbad-git-server.git sinbad-portal-local
cd sinbad-portal-local
```

### 2. Настроить автоматический pull (опционально)

Можно настроить автоматический pull через cron или использовать watch:

```bash
# Вариант 1: Cron (каждые 5 минут)
crontab -e
# Добавить:
*/5 * * * * cd ~/Desktop/sinbad-portal-local && git pull origin main > /dev/null 2>&1

# Вариант 2: Watch скрипт (вручную запускать)
watch -n 60 'cd ~/Desktop/sinbad-portal-local && git pull origin main'
```

### 3. Ручной pull

```bash
cd ~/Desktop/sinbad-portal-local
git pull origin main
```

## Рабочий процесс

### На сервере (при изменениях)

```bash
cd /var/www/gradaeronaut.com
git add .
git commit -m "Описание изменений"
# → Автоматически делается push в origin main через post-commit hook
```

### На Mac (получение изменений)

```bash
cd ~/Desktop/sinbad-portal-local
git pull origin main
```

## Проверка

### Проверить, что origin настроен на сервере

```bash
cd /var/www/gradaeronaut.com
git remote -v
```

Должно показать:
```
origin  https://github.com/GradAeronaut/sinbad-git-server.git (fetch)
origin  https://github.com/GradAeronaut/sinbad-git-server.git (push)
```

### Проверить, что post-commit hook работает

```bash
cd /var/www/gradaeronaut.com
echo "test" > test.txt
git add test.txt
git commit -m "test commit"
# Должен автоматически сделать push (если origin настроен)
git log --oneline -1
rm test.txt
git add test.txt
git commit -m "remove test"
```

## Troubleshooting

### Ошибка: "origin does not appear to be a git repository"

Настройте origin remote (см. раздел "Настройка на сервере")

### Ошибка: "Permission denied (publickey)"

Настройте SSH ключ для GitHub (см. раздел "Настройка SSH ключа")

### Push не происходит автоматически

Проверьте, что post-commit hook исполняемый:
```bash
chmod +x .git/hooks/post-commit
```

### На Mac не получаются изменения

Убедитесь, что:
1. Репозиторий клонирован с правильного remote
2. Вы в правильной директории
3. Нет локальных изменений, которые конфликтуют


