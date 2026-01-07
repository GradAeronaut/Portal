# GradAeronaut.com - Portal & KNEEBOARD

Интегрированный ПОРТАЛ (Sinbad) и KNEEBOARD на базе XenForo с системой единого входа (SSO).

## Структура проекта

```
/var/www/gradaeronaut.com/
├── app/                    # Бэкенд приложения портала
│   ├── api/               # API endpoints
│   ├── oauth/             # OAuth интеграции (Google)
│   ├── sso/               # SSO с форумом XenForo
│   └── xf/                # XenForo интеграции
├── auth/                  # Страницы аутентификации
├── forum/                 # XenForo 2.3.7 (KNEEBOARD, внутренний путь)
├── config/                # Конфигурационные файлы
├── docs/                  # Документация
├── tools/                 # Утилиты и скрипты
│   ├── backup_xenforo_db.sh
│   ├── transfer_backups_to_mac.sh
│   └── sql/              # SQL миграции
└── nginx/                 # Конфигурация Nginx

/var/backups/xenforo/      # Автоматические дампы БД форума
```

## Основные возможности

- ✅ **Портал** - главная страница с информацией о проекте
- ✅ **KNEEBOARD** - коммуникационная панель сообщества на `/kneeboard` (публично)
- ✅ **SSO аутентификация** - единый вход между порталом и KNEEBOARD
- ✅ **Google OAuth** - вход через Google аккаунт
- ✅ **Автоматические бэкапы** - ежедневные дампы БД форума
- ✅ **Git workflow** - версионирование и деплой через GitHub

## Быстрый старт

### Требования

- **OS:** Linux (Ubuntu/Debian)
- **PHP:** 8.1+
- **MySQL/MariaDB:** 5.7+ / 10.2+
- **Nginx:** 1.18+
- **Git:** 2.x

### Deployment

```bash
# 1. Клонировать репозиторий
cd /var/www
git clone https://github.com/GradAeronaut/sinbad-git-server.git gradaeronaut.com

# 2. Настроить права доступа
sudo chown -R www-data:www-data gradaeronaut.com
sudo chmod -R 755 gradaeronaut.com

# 3. Создать конфигурационные файлы из примеров
cp config/google_oauth.php.example config/google_oauth.php
# Отредактировать config/google_oauth.php с реальными credentials

# 4. Импортировать базу данных
mysql -u root -p < tools/sql/sinbad-tables.sql

# 5. Настроить XenForo
cd forum
php cmd.php xf:install

# 6. Настроить Nginx
sudo ln -s /var/www/gradaeronaut.com/nginx/sites-available/sinbad.conf \
  /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

# 7. Настроить автоматические бэкапы
sudo crontab -e
# Добавить: 0 2 * * * /var/www/gradaeronaut.com/tools/backup_xenforo_db.sh >> /var/log/xenforo_backup.log 2>&1
```

## Документация

📚 **Основная документация:**

- [BACKUP_AND_GIT_SETUP.md](docs/BACKUP_AND_GIT_SETUP.md) - Настройка Git и автоматических бэкапов
- [GITHUB_SETUP.md](docs/GITHUB_SETUP.md) - Подключение к GitHub и deployment
- [SSO_INTEGRATION_GUIDE.md](docs/SSO_INTEGRATION_GUIDE.md) - Интеграция SSO портала и KNEEBOARD
- [GOOGLE_OAUTH_SETUP.md](docs/GOOGLE_OAUTH_SETUP.md) - Настройка Google OAuth

## Бэкапы и восстановление

### Автоматические бэкапы

Бэкапы базы данных форума создаются автоматически каждый день в 2:00 UTC:

```bash
# Просмотр бэкапов
ls -lh /var/backups/xenforo/

# Ручной запуск бэкапа
sudo /var/www/gradaeronaut.com/tools/backup_xenforo_db.sh
```

### Передача бэкапов на локальный Mac

```bash
# С сервера на Mac
sudo /var/www/gradaeronaut.com/tools/transfer_backups_to_mac.sh user@mac-ip:/path/

# Или с Mac забрать с сервера
rsync -avz user@server:/var/backups/xenforo/ ~/backups/xenforo/
```

### Восстановление из бэкапа

```bash
# Восстановить базу данных
gunzip -c /var/backups/xenforo/xenforo_backup_YYYY-MM-DD_HH-MM-SS.sql.gz | \
  mysql -u forum_user -p sinbad_forum_db
```

## Git Workflow

### Получение обновлений (deployment)

```bash
cd /var/www/gradaeronaut.com
git pull origin main
sudo systemctl reload nginx
sudo systemctl restart php8.1-fpm
```

### Отправка изменений

```bash
cd /var/www/gradaeronaut.com
git add .
git commit -m "Описание изменений"
git push origin main
```

## Базы данных

Проект использует две базы данных:

1. **Portal** - база данных портала
   - Пользователи портала
   - OAuth токены
   - SSO токены

2. **sinbad_forum_db** - база данных форума XenForo
   - Пользователи форума
   - Посты и темы
   - Настройки форума

## Безопасность

⚠️ **Не коммитить в Git:**

- `config/google_oauth.php` - OAuth credentials
- `forum/internal_data/` - сессии и кэш
- `forum/data/` - пользовательские загрузки
- `*.sql.gz`, `*.sql.bak` - дампы БД

Все чувствительные файлы уже добавлены в `.gitignore`.

## Мониторинг

### Логи

```bash
# Логи бэкапов
tail -f /var/log/xenforo_backup.log

# Логи Nginx
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log

# Логи PHP-FPM
tail -f /var/log/php8.1-fpm.log
```

### Проверка состояния

```bash
# Статус Git
cd /var/www/gradaeronaut.com
git status

# Размер бэкапов
du -sh /var/backups/xenforo/

# Проверка cron
sudo crontab -l | grep xenforo
```

## Troubleshooting

### Форум не работает

```bash
# Проверить права
sudo chown -R www-data:www-data /var/www/gradaeronaut.com/forum

# Проверить Nginx конфигурацию
sudo nginx -t

# Перезапустить сервисы
sudo systemctl restart nginx
sudo systemctl restart php8.1-fpm
```

### SSO не работает

```bash
# Проверить таблицу sso_tokens
mysql -u sinbad_user -p Portal -e "SELECT * FROM sso_tokens ORDER BY created_at DESC LIMIT 5;"

# Проверить логи
tail -f /var/log/nginx/error.log | grep sso
```

### Git проблемы

```bash
# Проверить remote
git remote -v

# Проверить SSH доступ к GitHub
ssh -T git@github.com

# Сбросить локальные изменения
git stash
git pull origin main
```

## Техническая поддержка

При возникновении проблем проверьте:

1. Логи сервера и приложения
2. Конфигурацию Nginx и PHP-FPM
3. Права доступа к файлам
4. Статус баз данных

## Лицензия

Proprietary - Все права защищены

---

**Production Server**: `/var/www/gradaeronaut.com`  
**XenForo Version**: 2.3.7  
**PHP Version**: 8.1+  
**Last Updated**: December 5, 2025

# REQUIRED ON SERVER (ONE TIME)
chmod -R 775 forum/src/addons



