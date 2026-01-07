# ИНСТРУКЦИИ ДЛЯ ФИНАЛЬНОГО ДЕПЛОЯ

## ⚠️ ВАЖНО
Выполните эти команды **на сервере** через SSH.

---

## 1. ПОДКЛЮЧЕНИЕ К СЕРВЕРУ

```bash
ssh user@your-server
# или как вы обычно подключаетесь
```

---

## 2. ОБНОВЛЕНИЕ КОДА НА СЕРВЕРЕ

Выполните строго по порядку:

```bash
cd /var/www/gradaeronaut.com
git fetch origin
git reset --hard origin/main
git log -1 --oneline
```

**Ожидаемый результат:**
```
c807906 Header layout update: 13vh, avatar+gauge alignment
```

---

## 3. ПЕРЕЗАГРУЗКА СЕРВИСОВ

```bash
sudo systemctl reload nginx
sudo systemctl restart php-fpm
```

---

## 4. ПРОВЕРКА В БРАУЗЕРЕ

Откройте страницу `/shape-sinbad-new/` и проверьте:

✅ **Маркер SERVER-OK-v2** появился в правом нижнем углу  
✅ В DevTools для `.right-block` → `bottom: 15px` (не 20px)  
✅ Размеры аватара и прибора → `90px` (не 112px)  
✅ Левый блок содержит только 2 строки: имя + "ID · PREMIUM"  

---

## 5. УДАЛЕНИЕ МАРКЕРА (ПОСЛЕ ПОДТВЕРЖДЕНИЯ)

После того, как убедитесь, что все работает, выполните **локально**:

```bash
cd /Users/user/SinbadRepo/sinbad-portal-local
# (команды будут выполнены автоматически после подтверждения)
```

Или вручную:
```bash
git add shape-sinbad-new/style.css
git commit -m "Remove server check marker"
git push origin main
```

Затем на сервере снова:
```bash
cd /var/www/gradaeronaut.com
git fetch origin
git reset --hard origin/main
```

---

## ЕСЛИ ИЗМЕНЕНИЯ НЕ ВИДНЫ

1. Проверьте путь к файлу на сервере:
   ```bash
   ls -la /var/www/gradaeronaut.com/shape-sinbad-new/style.css
   ```

2. Проверьте, какой файл реально отдается:
   ```bash
   grep -n "bottom:" /var/www/gradaeronaut.com/shape-sinbad-new/style.css | head -3
   ```

3. Проверьте версию коммита:
   ```bash
   git log -1 --oneline
   ```

4. Очистите кэш браузера: `Cmd + Shift + R` (Mac) или `Ctrl + Shift + R` (Windows)





