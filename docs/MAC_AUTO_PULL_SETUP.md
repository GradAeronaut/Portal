# Настройка автоматического получения обновлений на Mac

## Обзор

Настроен автоматический pull изменений с GitHub на Mac через launchd service. Сервис проверяет наличие новых коммитов каждые 5 минут и автоматически выполняет `git pull --rebase`.

## Файлы

1. **`tools/mac-auto-pull.sh`** - Скрипт для автоматического pull
2. **`tools/com.sinbad.portal.autopull.plist`** - Конфигурация launchd service

## Установка

### Шаг 1: Скопировать файлы на Mac

Скопируйте файлы из репозитория на Mac:

```bash
# На Mac
cd ~/Desktop/sinbad-portal-local

# Убедитесь, что файлы скопированы из GitHub
git pull origin main

# Сделать скрипт исполняемым
chmod +x tools/mac-auto-pull.sh
```

### Шаг 2: Обновить пути в plist файле

Отредактируйте файл `tools/com.sinbad.portal.autopull.plist` и замените `/Users/user` на ваш username:

```bash
# Узнать ваш username
whoami

# Отредактировать plist файл
nano ~/Desktop/sinbad-portal-local/tools/com.sinbad.portal.autopull.plist
```

Замените все вхождения `/Users/user` на `/Users/ваш_username`.

### Шаг 3: Установить launchd service

```bash
# Скопировать plist в LaunchAgents
cp ~/Desktop/sinbad-portal-local/tools/com.sinbad.portal.autopull.plist \
   ~/Library/LaunchAgents/com.sinbad.portal.autopull.plist

# Загрузить service
launchctl load ~/Library/LaunchAgents/com.sinbad.portal.autopull.plist

# Запустить service
launchctl start com.sinbad.portal.autopull
```

### Шаг 4: Проверить статус

```bash
# Проверить, что service запущен
launchctl list | grep sinbad

# Посмотреть логи
tail -f ~/Library/Logs/sinbad-portal-auto-pull.log
```

## Управление сервисом

### Запуск

```bash
launchctl start com.sinbad.portal.autopull
```

### Остановка

```bash
launchctl stop com.sinbad.portal.autopull
```

### Перезагрузка

```bash
launchctl unload ~/Library/LaunchAgents/com.sinbad.portal.autopull.plist
launchctl load ~/Library/LaunchAgents/com.sinbad.portal.autopull.plist
```

### Удаление

```bash
launchctl unload ~/Library/LaunchAgents/com.sinbad.portal.autopull.plist
rm ~/Library/LaunchAgents/com.sinbad.portal.autopull.plist
```

## Настройка интервала проверки

По умолчанию проверка выполняется каждые 5 минут (300 секунд).

Чтобы изменить интервал, отредактируйте `StartInterval` в plist файле:

```xml
<key>StartInterval</key>
<integer>300</integer>  <!-- 300 секунд = 5 минут -->
```

Доступные значения:
- `60` - каждую минуту
- `300` - каждые 5 минут (по умолчанию)
- `600` - каждые 10 минут
- `1800` - каждые 30 минут

## Логи

Логи сохраняются в:
- **Успешные операции**: `~/Library/Logs/sinbad-portal-auto-pull.log`
- **Ошибки**: `~/Library/Logs/sinbad-portal-auto-pull.error.log`

Просмотр логов:
```bash
# Последние 50 строк
tail -50 ~/Library/Logs/sinbad-portal-auto-pull.log

# В реальном времени
tail -f ~/Library/Logs/sinbad-portal-auto-pull.log
```

## Как это работает

1. **LaunchAgent** запускается автоматически при входе в систему
2. Каждые 5 минут выполняется скрипт `mac-auto-pull.sh`
3. Скрипт:
   - Проверяет наличие новых коммитов через `git fetch`
   - Если есть новые коммиты, сохраняет локальные изменения в stash
   - Выполняет `git pull --rebase origin main`
   - Восстанавливает локальные изменения из stash

## Безопасность

- Локальные незакоммиченные изменения автоматически сохраняются в stash перед pull
- Используется `--rebase` для чистой истории коммитов
- Все операции логируются

## Troubleshooting

### Service не запускается

```bash
# Проверить ошибки
launchctl list | grep sinbad

# Проверить логи ошибок
cat ~/Library/Logs/sinbad-portal-auto-pull.error.log

# Проверить права на файлы
ls -la ~/Desktop/sinbad-portal-local/tools/mac-auto-pull.sh
chmod +x ~/Desktop/sinbad-portal-local/tools/mac-auto-pull.sh
```

### Pull не выполняется

1. Проверьте, что репозиторий клонирован правильно:
   ```bash
   cd ~/Desktop/sinbad-portal-local
   git remote -v
   ```

2. Проверьте SSH ключи для GitHub:
   ```bash
   ssh -T git@github.com
   ```

3. Проверьте логи:
   ```bash
   tail -f ~/Library/Logs/sinbad-portal-auto-pull.log
   ```

### Конфликты при pull

Если возникают конфликты при pull:
1. Скрипт автоматически сохранит изменения в stash
2. Разрешите конфликты вручную:
   ```bash
   cd ~/Desktop/sinbad-portal-local
   git pull --rebase origin main
   # Разрешить конфликты
   git rebase --continue
   ```

## Альтернатива: Использование cron (не рекомендуется)

Если по какой-то причине launchd не подходит, можно использовать cron:

```bash
# Открыть crontab
crontab -e

# Добавить строку (каждые 5 минут)
*/5 * * * * cd ~/Desktop/sinbad-portal-local && /bin/bash tools/mac-auto-pull.sh
```

Однако launchd предпочтительнее, так как:
- Автоматически запускается при входе в систему
- Лучше интегрирован с macOS
- Имеет встроенное логирование


