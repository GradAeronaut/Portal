# Устаревшие скрипты

Следующие скрипты устарели после консолидации Git репозиториев:

- `push-to-server.sh` - использовался для push через remote `server` (удалён)
- `pull-from-server.sh` - использовался для pull через remote `server` (удалён)
- `sync-with-server.sh` - использовался для синхронизации с remote `server` (удалён)

**Текущий workflow:**

Используйте стандартные Git команды для работы с `origin` (sinbad-git-server.git):

```bash
# Получить изменения
git fetch origin
git pull origin main

# Отправить изменения
git push origin main

# На сервере: обновить код
ssh root@159.198.74.241 "cd /var/www/gradaeronaut.com && git fetch origin && git reset --hard origin/main"
```

**Единственный remote:** `origin` → `https://github.com/GradAeronaut/sinbad-git-server.git`



