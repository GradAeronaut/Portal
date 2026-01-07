# Настройка Git-архитектуры и автоматических бэкапов

## Обзор

Этот документ описывает настройку серверной части проекта gradaeronaut.com для работы с Git и автоматическими дампами базы данных XenForo.

## Структура проекта

```
/var/www/gradaeronaut.com/
├── forum/                      # XenForo форум
│   ├── src/                   # Исходный код форума
│   ├── data/                  # Данные (исключено из git)
│   ├── internal_data/         # Внутренние данные (исключено из git)
│   └── ...
├── app/                       # Приложение портала
├── auth/                      # Аутентификация
├── config/                    # Конфигурационные файлы
├── tools/                     # Утилиты и скрипты
│   ├── backup_xenforo_db.sh
│   └── transfer_backups_to_mac.sh
└── docs/                      # Документация

/var/backups/xenforo/          # Директория для дампов БД
```

## Git Configuration

### Исключенные файлы (.gitignore)

Следующие файлы и директории исключены из Git:

- `forum/internal_data/*` - Внутренние данные форума (сессии, кэш)
- `forum/data/*` - Пользовательские загрузки и аватары
- `forum/install/*` - Файлы установки (не нужны в продакшене)
- `xenforo_*.zip` - Архивы дистрибутива XenForo
- `*.sql.gz`, `*.sql.bak` - Дампы баз данных
- `config/google_oauth.php` - Конфиденциальная OAuth-конфигурация

### Ветки

- **main** - основная ветка, источник истины для продакшена
- Все изменения деплоятся через `git pull origin main`

## Автоматические бэкапы базы данных

### Конфигурация бэкапов

**Скрипт:** `/var/www/gradaeronaut.com/tools/backup_xenforo_db.sh`

**Параметры:**
- База данных: `sinbad_forum_db`
- Директория бэкапов: `/var/backups/xenforo/`
- Формат имени файла: `xenforo_backup_YYYY-MM-DD_HH-MM-SS.sql.gz`
- Период хранения: 30 дней
- Права доступа: 640 (www-data:www-data)

### Cron настройка

Для ежедневного автоматического создания дампов БД добавьте следующую строку в crontab:

```bash
# Добавить в crontab root:
sudo crontab -e

# Добавить строку:
0 2 * * * /var/www/gradaeronaut.com/tools/backup_xenforo_db.sh >> /var/log/xenforo_backup.log 2>&1
```

Это запустит бэкап каждый день в 2:00 ночи по UTC.

### Проверка логов бэкапов

```bash
# Просмотр логов
tail -f /var/log/xenforo_backup.log

# Список бэкапов
ls -lh /var/backups/xenforo/

# Размер всех бэкапов
du -sh /var/backups/xenforo/
```

### Ручной запуск бэкапа

```bash
sudo /var/www/gradaeronaut.com/tools/backup_xenforo_db.sh
```

## Передача бэкапов на локальный Mac

### Скрипт передачи

**Скрипт:** `/var/www/gradaeronaut.com/tools/transfer_backups_to_mac.sh`

⚠️ **ВАЖНО:** Скрипт предназначен только для ручного использования, автоматизация не включена.

### Использование

#### Вариант 1: С сервера на Mac через rsync

```bash
# На сервере:
sudo /var/www/gradaeronaut.com/tools/transfer_backups_to_mac.sh user@192.168.1.100:/Users/user/backups/xenforo/
```

#### Вариант 2: С Mac забрать с сервера через rsync

```bash
# На Mac:
rsync -avz --progress \
  user@server-ip:/var/backups/xenforo/ \
  ~/backups/xenforo/
```

#### Вариант 3: С Mac забрать через SCP

```bash
# На Mac - скопировать все бэкапы:
scp user@server-ip:/var/backups/xenforo/*.sql.gz ~/backups/xenforo/

# Или конкретный файл:
scp user@server-ip:/var/backups/xenforo/xenforo_backup_2025-12-05_09-58-15.sql.gz ~/backups/
```

### Проверка прав доступа

```bash
# На сервере проверить права:
ls -lh /var/backups/xenforo/

# Должно быть:
# -rw-r----- 1 www-data www-data [size] [date] xenforo_backup_*.sql.gz
```

### Настройка SSH-ключей (опционально)

Для передачи без пароля настройте SSH-ключи:

```bash
# На Mac:
ssh-keygen -t ed25519 -C "your_email@example.com"

# Скопировать ключ на сервер:
ssh-copy-id user@server-ip

# Проверить доступ:
ssh user@server-ip "ls /var/backups/xenforo/"
```

## Git Operations

### Подготовка к первому commit

```bash
cd /var/www/gradaeronaut.com

# Проверить статус
git status

# Добавить все файлы (учитывая .gitignore)
git add .

# Сделать первый полноценный commit
git commit -m "Initial commit: portal + XenForo forum integration"
```

### Настройка GitHub remote

```bash
# Добавить remote репозиторий
git remote add origin https://github.com/GradAeronaut/sinbad-git-server.git

# Проверить remote
git remote -v

# Первый push
git push -u origin main
```

### Деплой изменений

```bash
# На сервере:
cd /var/www/gradaeronaut.com

# Забрать изменения из GitHub
git pull origin main

# Проверить статус
git status

# При необходимости перезапустить сервисы
sudo systemctl reload nginx
sudo systemctl restart php8.1-fpm  # или php7.4-fpm в зависимости от версии
```

### Проверка готовности к git pull

```bash
# Проверить текущую ветку
git branch

# Проверить remote
git remote -v

# Проверить статус (не должно быть незакоммиченных изменений)
git status

# Проверить что можно получить обновления
git fetch origin
git log HEAD..origin/main --oneline
```

## Восстановление из бэкапа

### Восстановление базы данных из дампа

```bash
# Распаковать дамп
gunzip -c /var/backups/xenforo/xenforo_backup_2025-12-05_09-58-15.sql.gz > /tmp/restore.sql

# Восстановить базу данных
mysql -u forum_user -p sinbad_forum_db < /tmp/restore.sql

# Удалить временный файл
rm /tmp/restore.sql
```

### Восстановление с локального Mac

```bash
# Скопировать бэкап с Mac на сервер
scp ~/backups/xenforo/xenforo_backup_2025-12-05_09-58-15.sql.gz \
  user@server-ip:/tmp/

# На сервере восстановить:
gunzip -c /tmp/xenforo_backup_2025-12-05_09-58-15.sql.gz | \
  mysql -u forum_user -p sinbad_forum_db
```

## Мониторинг и обслуживание

### Проверка дискового пространства

```bash
# Размер бэкапов
du -sh /var/backups/xenforo/

# Доступное место
df -h /var/backups
```

### Очистка старых бэкапов (если нужно вручную)

```bash
# Удалить бэкапы старше 60 дней
find /var/backups/xenforo/ -name "xenforo_backup_*.sql.gz" -mtime +60 -delete

# Список бэкапов по дате
ls -lt /var/backups/xenforo/
```

### Проверка состояния cron

```bash
# Просмотр crontab
sudo crontab -l

# Проверка логов cron
grep CRON /var/log/syslog | tail -20
```

## Безопасность

### Рекомендации по безопасности

1. **Права доступа к бэкапам:** Только www-data и root должны иметь доступ
2. **SSH-ключи:** Используйте SSH-ключи вместо паролей для передачи файлов
3. **Конфиденциальные данные:** Не коммитьте пароли и токены в Git
4. **Шифрование бэкапов:** Рассмотрите возможность шифрования бэкапов перед передачей

### Безопасная передача с шифрованием

```bash
# Зашифровать бэкап перед передачей (опционально)
gpg --symmetric --cipher-algo AES256 \
  /var/backups/xenforo/xenforo_backup_2025-12-05_09-58-15.sql.gz

# Передать зашифрованный файл
scp /var/backups/xenforo/xenforo_backup_2025-12-05_09-58-15.sql.gz.gpg \
  user@mac:/path/to/destination/

# На Mac расшифровать:
gpg --decrypt xenforo_backup_2025-12-05_09-58-15.sql.gz.gpg > \
  xenforo_backup_2025-12-05_09-58-15.sql.gz
```

## Контакты и поддержка

При возникновении проблем проверьте:
1. Логи бэкапов: `/var/log/xenforo_backup.log`
2. Логи Git операций
3. Логи cron: `/var/log/syslog`
4. Права доступа к файлам и директориям

---

**Дата создания:** 5 декабря 2025  
**Версия:** 1.0



