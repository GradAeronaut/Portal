# 🚀 Быстрый старт: Подключение к GitHub

Сервер подготовлен и готов к подключению к GitHub. Выполните следующие шаги:

## ✅ Что уже готово

- Git-репозиторий инициализирован и настроен
- Ветка `main` с 4 коммитами
- Все файлы закоммичены (working tree clean)
- Форум XenForo включен в репозиторий
- Автоматические бэкапы БД настроены (каждый день в 2:00 UTC)
- Полная документация создана

## 📋 Следующие шаги (5 минут)

### 1️⃣ Настроить Git пользователя (30 секунд)

```bash
cd /var/www/gradaeronaut.com
git config user.name "Your Name"
git config user.email "your@email.com"
```

### 2️⃣ Создать SSH ключ для GitHub (1 минута)

```bash
# Создать ключ
ssh-keygen -t ed25519 -C "your@email.com"
# Нажмите Enter 3 раза (для дефолтных настроек)

# Показать публичный ключ
cat ~/.ssh/id_ed25519.pub
```

**Скопируйте вывод и добавьте на GitHub:**
1. Зайдите на https://github.com/settings/keys
2. Нажмите "New SSH key"
3. Вставьте ключ и сохраните

```bash
# Проверьте соединение
ssh -T git@github.com
# Должно вывести: "Hi username! You've successfully authenticated..."
```

### 3️⃣ Создать репозиторий на GitHub (1 минута)

**Вариант A: Через веб-интерфейс**
1. Зайдите на https://github.com/new
2. Имя: `gradaeronaut.com` (или любое другое)
3. Выберите: **Private** (рекомендуется)
4. **НЕ добавляйте** README, .gitignore, license
5. Нажмите "Create repository"

**Вариант B: Через командную строку (если установлен gh)**
```bash
gh repo create gradaeronaut.com --private --source=. --remote=origin
```

### 4️⃣ Добавить GitHub remote (30 секунд)

```bash
cd /var/www/gradaeronaut.com

# Добавить remote (sinbad-git-server.git)
git remote add origin https://github.com/GradAeronaut/sinbad-git-server.git

# Проверить
git remote -v
```

### 5️⃣ Отправить код на GitHub (1 минута)

```bash
cd /var/www/gradaeronaut.com

# Первый push
git push -u origin main

# Проверить
git log --oneline -5
```

## 🎉 Готово!

После выполнения этих шагов:

✅ Код будет на GitHub  
✅ Можно делать `git pull origin main` для deployment  
✅ Можно делать `git push origin main` для отправки изменений  
✅ Автоматические бэкапы БД работают  

## 📚 Дальнейшие действия

### Deployment (получение обновлений)

```bash
cd /var/www/gradaeronaut.com
git pull origin main
sudo systemctl reload nginx
sudo systemctl restart php8.1-fpm
```

### Проверка бэкапов

```bash
# Список бэкапов
ls -lh /var/backups/xenforo/

# Логи бэкапов
tail -f /var/log/xenforo_backup.log

# Ручной бэкап
sudo /var/www/gradaeronaut.com/tools/backup_xenforo_db.sh
```

### Передача бэкапов на Mac

```bash
# С Mac забрать бэкапы с сервера
rsync -avz user@server-ip:/var/backups/xenforo/ ~/backups/xenforo/
```

## 📖 Документация

Подробная информация в следующих файлах:

- `README.md` - Обзор проекта
- `docs/GITHUB_SETUP.md` - Полное руководство по GitHub
- `docs/BACKUP_AND_GIT_SETUP.md` - Руководство по бэкапам
- `docs/SETUP_SUMMARY.md` - Итоговый отчет

## ❓ Troubleshooting

**Ошибка: "Permission denied (publickey)"**
```bash
# Проверьте SSH ключ
ssh -T git@github.com
# Если не работает, используйте HTTPS (уже установлено):
# git remote set-url origin https://github.com/GradAeronaut/sinbad-git-server.git
```

**Ошибка при git pull**
```bash
# Установите upstream
git branch --set-upstream-to=origin/main main
```

## 🔗 Полезные ссылки

- GitHub SSH keys: https://github.com/settings/keys
- GitHub new repo: https://github.com/new
- Документация Git: https://git-scm.com/doc

---

**Время выполнения:** ~5 минут  
**Дата:** 5 декабря 2025  
**Статус:** Ready to deploy 🚀



