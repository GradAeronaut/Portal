# Google OAuth2 Integration - Changelog

## Дата: 21 ноября 2025

### Добавленные файлы

1. **app/google_start.php**
   - Инициализация процесса Google OAuth2
   - Генерация state для CSRF-защиты
   - Перенаправление на Google для авторизации

2. **app/google_callback.php**
   - Обработка callback от Google
   - Обмен code на access_token
   - Получение userinfo от Google
   - Поиск/создание пользователя в БД
   - Создание сессии и установка cookie

3. **config/google_oauth.php**
   - Конфигурация для Google OAuth2
   - Хранит Client ID, Client Secret, scopes
   - ВНИМАНИЕ: Добавлен в .gitignore (содержит секретные данные)

4. **config/google_oauth.php.example**
   - Пример конфигурационного файла
   - Можно добавить в git для разработчиков

5. **migration-google-oauth.sql**
   - SQL-миграция для добавления поля google_id в таблицу users
   - Добавляет уникальный индекс на google_id

6. **GOOGLE_OAUTH_SETUP.md**
   - Полная инструкция по настройке Google OAuth2
   - Шаги создания проекта в Google Cloud Console
   - Настройка OAuth consent screen
   - Troubleshooting и FAQ

7. **.gitignore**
   - Исключает config/google_oauth.php из системы контроля версий
   - Дополнительные правила для IDE, logs, cache

8. **CHANGELOG_GOOGLE_OAUTH.md**
   - Этот файл - сводка всех изменений

### Измененные файлы

1. **auth/login/script.js**
   - Обновлена функция initGoogleHandler()
   - Google-кнопка теперь перенаправляет на /app/google_start.php
   - Передает параметр next для редиректа после входа

2. **auth/familiarity/script.js**
   - Обновлена функция initGoogleHandler()
   - Google-кнопка перенаправляет на /app/google_start.php

3. **auth/modern-form/script.js**
   - Обновлена функция initGoogleHandler()
   - Google-кнопка перенаправляет на /app/google_start.php

### Изменения в базе данных

Необходимо выполнить миграцию:

```sql
ALTER TABLE `users` 
ADD COLUMN `google_id` VARCHAR(255) DEFAULT NULL AFTER `public_id`,
ADD UNIQUE KEY `google_id` (`google_id`);
```

### Как это работает

#### Процесс входа/регистрации через Google:

1. Пользователь нажимает "Sign In with Google" на странице входа
2. → Перенаправление на `/app/google_start.php`
3. → Google OAuth authorization screen
4. → Пользователь авторизуется в Google
5. → Callback на `/app/google_callback.php?code=...&state=...`
6. → Обмен code на access_token
7. → Получение userinfo (email, name, google_id)
8. → Поиск пользователя в БД:
   - Если найден по google_id → вход
   - Если найден по email → привязка google_id и вход
   - Если не найден → создание нового пользователя и вход
9. → Создание сессии в таблице sessions
10. → Установка cookie session_token
11. → Перенаправление на /account (или next URL)

#### Безопасность:

✅ CSRF-защита через state parameter  
✅ HttpOnly cookies  
✅ SameSite=Lax  
✅ Проверка verified_email от Google  
✅ Уникальные индексы в БД  
✅ Секретные данные вынесены в config  

### Что нужно сделать для запуска

1. **Выполнить SQL-миграцию:**
   ```bash
   mysql -u sinbad_user -p sinbad_db < migration-google-oauth.sql
   ```

2. **Создать OAuth Client в Google Cloud Console:**
   - Следовать инструкциям в GOOGLE_OAUTH_SETUP.md
   - Получить Client ID и Client Secret

3. **Настроить конфигурацию:**
   ```bash
   cp config/google_oauth.php.example config/google_oauth.php
   nano config/google_oauth.php
   # Вставить реальные Client ID и Client Secret
   ```

4. **Добавить Authorized redirect URIs в Google Cloud Console:**
   ```
   http://localhost/app/google_callback.php
   https://yourdomain.com/app/google_callback.php
   ```

5. **Протестировать:**
   - Открыть /auth/login/
   - Нажать "Sign In with Google"
   - Авторизоваться
   - Проверить редирект на /account

### Известные ограничения

1. **Тестовый режим OAuth consent screen:**
   - Работает только для добавленных test users
   - Для продакшена нужно опубликовать приложение

2. **Требуется cURL:**
   - PHP должен быть собран с поддержкой cURL
   - Проверить: `php -m | grep curl`

3. **HTTPS в продакшене:**
   - Google может требовать HTTPS для redirect URI
   - В development можно использовать http://localhost

### Возможные улучшения

- [ ] Добавить логирование OAuth попыток в gateway_log
- [ ] Добавить возможность отвязать Google аккаунт
- [ ] Добавить отображение аватара из Google
- [ ] Поддержка refresh_token для длительных сессий
- [ ] Миграция данных пользователя из Google (birthday, locale)
- [ ] Интеграция с другими OAuth провайдерами (Facebook, GitHub)

### Контакты и поддержка

При возникновении проблем:
1. Проверьте GOOGLE_OAUTH_SETUP.md
2. Проверьте логи PHP и веб-сервера
3. Проверьте настройки в Google Cloud Console
4. Убедитесь, что все файлы имеют правильные права доступа

---

**Версия:** 1.0  
**Автор:** Sinbad Portal Development Team  
**Дата:** 21 ноября 2025

