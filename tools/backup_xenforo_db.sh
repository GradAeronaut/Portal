#!/bin/bash
#
# XenForo Database Backup Script
# Автоматическое создание дампа БД форума с датированием
#
# Использование: ./backup_xenforo_db.sh
# Для cron: 0 2 * * * /var/www/gradaeronaut.com/tools/backup_xenforo_db.sh >> /var/log/xenforo_backup.log 2>&1
#

# Конфигурация
DB_NAME="sinbad_forum_db"
DB_USER="forum_user"
DB_PASS="StrongPass123!"
BACKUP_DIR="/var/backups/xenforo"
DATE=$(date +"%Y-%m-%d_%H-%M-%S")
BACKUP_FILE="${BACKUP_DIR}/xenforo_backup_${DATE}.sql.gz"
RETENTION_DAYS=30  # Хранить бэкапы 30 дней

# Проверка существования директории
if [ ! -d "$BACKUP_DIR" ]; then
    echo "Error: Backup directory $BACKUP_DIR does not exist"
    exit 1
fi

# Создание дампа БД
echo "[$(date)] Starting XenForo database backup..."
mysqldump -u"${DB_USER}" -p"${DB_PASS}" \
    --single-transaction \
    --quick \
    --lock-tables=false \
    --routines \
    --triggers \
    --events \
    "${DB_NAME}" | gzip > "${BACKUP_FILE}"

# Проверка успешности
if [ $? -eq 0 ]; then
    FILESIZE=$(du -h "${BACKUP_FILE}" | cut -f1)
    echo "[$(date)] Backup completed successfully: ${BACKUP_FILE} (${FILESIZE})"
    
    # Установка прав доступа
    chmod 640 "${BACKUP_FILE}"
    chown www-data:www-data "${BACKUP_FILE}"
    
    # Удаление старых бэкапов (старше RETENTION_DAYS дней)
    echo "[$(date)] Cleaning up old backups (older than ${RETENTION_DAYS} days)..."
    find "${BACKUP_DIR}" -name "xenforo_backup_*.sql.gz" -mtime +${RETENTION_DAYS} -delete
    
    # Подсчет общего количества бэкапов
    BACKUP_COUNT=$(find "${BACKUP_DIR}" -name "xenforo_backup_*.sql.gz" | wc -l)
    echo "[$(date)] Total backups retained: ${BACKUP_COUNT}"
else
    echo "[$(date)] Error: Backup failed!"
    exit 1
fi

echo "[$(date)] Backup process finished."
exit 0



