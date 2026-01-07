#!/bin/bash
#
# Script for transferring XenForo backups to local Mac
# Скрипт для передачи бэкапов на локальный Mac через SCP/rsync
#
# MANUAL USE ONLY - NOT AUTOMATED
# Использование вручную, НЕ автоматизировано
#
# Usage:
#   ./transfer_backups_to_mac.sh [remote_user@remote_host:/path/to/destination]
#
# Example:
#   ./transfer_backups_to_mac.sh user@192.168.1.100:/Users/user/backups/xenforo/
#

BACKUP_DIR="/var/backups/xenforo"
REMOTE_DEST="${1}"

# Проверка аргументов
if [ -z "$REMOTE_DEST" ]; then
    echo "Error: Remote destination not specified"
    echo "Usage: $0 user@host:/path/to/destination"
    echo ""
    echo "Example: $0 user@192.168.1.100:/Users/user/backups/xenforo/"
    exit 1
fi

# Проверка существования директории
if [ ! -d "$BACKUP_DIR" ]; then
    echo "Error: Backup directory $BACKUP_DIR does not exist"
    exit 1
fi

# Подсчет файлов для передачи
FILE_COUNT=$(find "$BACKUP_DIR" -name "xenforo_backup_*.sql.gz" | wc -l)
if [ "$FILE_COUNT" -eq 0 ]; then
    echo "No backup files found in $BACKUP_DIR"
    exit 0
fi

echo "Found $FILE_COUNT backup file(s) to transfer"
echo "Source: $BACKUP_DIR"
echo "Destination: $REMOTE_DEST"
echo ""
echo "Transfer method: rsync with compression"
echo ""

# Запрос подтверждения
read -p "Proceed with transfer? (y/n): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Transfer cancelled"
    exit 0
fi

# Передача через rsync
echo "[$(date)] Starting transfer..."
rsync -avz --progress \
    --partial \
    --human-readable \
    "${BACKUP_DIR}/xenforo_backup_"*.sql.gz \
    "${REMOTE_DEST}"

if [ $? -eq 0 ]; then
    echo "[$(date)] Transfer completed successfully"
    echo "Transferred $FILE_COUNT file(s) to $REMOTE_DEST"
else
    echo "[$(date)] Transfer failed with error code $?"
    exit 1
fi

echo ""
echo "Transfer summary:"
echo "  Source: $BACKUP_DIR"
echo "  Destination: $REMOTE_DEST"
echo "  Files: $FILE_COUNT"
echo ""
echo "Alternative transfer methods:"
echo "  SCP:    scp ${BACKUP_DIR}/xenforo_backup_*.sql.gz ${REMOTE_DEST}"
echo "  SFTP:   sftp ${REMOTE_DEST%%:*}"
echo ""

exit 0



