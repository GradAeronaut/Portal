# Настройка Google OAuth2 для Sinbad Portal

## Шаг 1: Создание проекта в Google Cloud Console

1. Перейдите на [Google Cloud Console](https://console.cloud.google.com/)
2. Создайте новый проект или выберите существующий
3. Убедитесь, что проект выбран в верхней панели

## Шаг 2: Включение Google+ API

1. В боковом меню выберите **APIs & Services** → **Library**
2. Найдите **Google+ API** или **Google Identity**
3. Нажмите **Enable**

## Шаг 3: Создание OAuth 2.0 Client ID

1. Перейдите в **APIs & Services** → **Credentials**
2. Нажмите **Create Credentials** → **OAuth client ID**
3. Если требуется, настройте **OAuth consent screen**:
   - User Type: **External** (для тестирования)
   - App name: **Sinbad Portal**
   - User support email: ваш email
   - Developer contact: ваш email
   - Scopes: добавьте `email`, `profile`, `openid`
   - Test users: добавьте тестовые email-адреса

4. Вернитесь к созданию OAuth client ID:
   - Application type: **Web application**
   - Name: **Sinbad Portal OAuth**
   - Authorized JavaScript origins:
     ```
     http://localhost
     https://yourdomain.com
     ```
   - Authorized redirect URIs:
     ```
     http://localhost/app/google_callback.php
     https://yourdomain.com/app/google_callback.php
     ```

5. Нажмите **Create**
6. Сохраните **Client ID** и **Client Secret**

## Шаг 4: Настройка проекта

### 4.1. Выполните SQL-миграцию

Выполните SQL-файл для добавления поля `google_id`:

```bash
mysql -u sinbad_user -p sinbad_db < migration-google-oauth.sql
```

Или выполните SQL напрямую в MySQL/MariaDB:

```sql
ALTER TABLE `users` 
ADD COLUMN `google_id` VARCHAR(255) DEFAULT NULL AFTER `public_id`,
ADD UNIQUE KEY `google_id` (`google_id`);
```

### 4.2. Обновите credentials в конфигурационном файле

Откройте файл **config/google_oauth.php** и замените placeholder-значения:

```php
return [
    'client_id' => 'YOUR_ACTUAL_CLIENT_ID.apps.googleusercontent.com',
    'client_secret' => 'YOUR_ACTUAL_CLIENT_SECRET',
    'scopes' => 'openid email profile',
    'redirect_uri_path' => '/app/google_callback.php',
];
```

Замените `YOUR_ACTUAL_CLIENT_ID` и `YOUR_ACTUAL_CLIENT_SECRET` на ваши реальные значения из Google Cloud Console.

**Важно:** Этот файл содержит секретные данные. Убедитесь, что он:
- Не добавлен в систему контроля версий (добавьте в `.gitignore`)
- Имеет правильные права доступа на сервере (например, `chmod 600`)
- Не доступен напрямую через веб-сервер

## Шаг 5: Тестирование

1. Откройте форму входа: `http://localhost/auth/login/`
2. Нажмите кнопку **Sign In with Google**
3. Вы будете перенаправлены на страницу авторизации Google
4. После успешной авторизации вы будете перенаправлены на `/account`

## Логика работы

### Процесс входа через Google:

1. **google_start.php**:
   - Генерирует state для защиты от CSRF
   - Перенаправляет на Google OAuth URL
   - Сохраняет параметр `next` для редиректа после входа

2. **google_callback.php**:
   - Получает `code` от Google
   - Обменивает `code` на `access_token`
   - Получает информацию о пользователе (email, name, google_id)
   - Ищет пользователя по `google_id` или `email`
   - Если пользователя нет - создаёт нового
   - Если есть по email - привязывает `google_id`
   - Создаёт сессию в БД
   - Устанавливает cookie `session_token`
   - Перенаправляет на `/account` или на `next` URL

### Поля в базе данных:

- `google_id` - уникальный ID пользователя из Google
- `email_verified` - автоматически устанавливается в `1` при входе через Google
- `status` - автоматически устанавливается в `active` при входе через Google
- `password_hash` - может быть `NULL` для пользователей, созданных через Google

## Безопасность

✅ **Реализовано:**
- CSRF-защита через state parameter
- Использование HTTPS (если доступен)
- HttpOnly cookies
- SameSite=Lax для cookies
- Проверка verified_email от Google
- Уникальные индексы в БД

⚠️ **Рекомендации:**
- Используйте HTTPS в продакшене
- Храните Client Secret в переменных окружения
- Ограничьте Authorized redirect URIs только вашими доменами
- Регулярно обновляйте зависимости

## Обработка ошибок

Google OAuth может вернуть пользователя с параметром `error` в URL:
- `?error=oauth_cancelled` - пользователь отказал в доступе
- `?error=oauth_invalid` - отсутствует code
- `?error=oauth_invalid_state` - CSRF атака или истёкший state
- `?error=oauth_token_failed` - не удалось получить access_token
- `?error=oauth_userinfo_failed` - не удалось получить данные пользователя
- `?error=database_error` - ошибка подключения к БД
- `?error=email_conflict` - email уже привязан к другому Google аккаунту

## Дополнительная настройка

### Изменение redirect URL после входа

По умолчанию после входа пользователь перенаправляется на `/account`.

Чтобы изменить это, можно передать параметр `next`:
```
/auth/login/?next=/dashboard
```

### Настройка scopes

Если нужно получить дополнительные данные от Google, измените scope в `google_start.php`:
```php
'scope' => 'openid email profile https://www.googleapis.com/auth/user.birthday.read',
```

Полный список scopes: https://developers.google.com/identity/protocols/oauth2/scopes

## Устранение проблем

### Ошибка: "redirect_uri_mismatch"
- Проверьте, что redirect URI в коде совпадает с URI в Google Cloud Console
- Убедитесь, что используете правильный протокол (http/https)
- Убедитесь, что нет лишних слэшей или параметров

### Ошибка: "invalid_client"
- Проверьте правильность Client ID и Client Secret
- Убедитесь, что OAuth client не удалён в Google Cloud Console

### Ошибка: "access_denied"
- Пользователь отказал в доступе
- Убедитесь, что приложение не заблокировано в настройках Google Account

### Пользователь не может войти (тестовый режим)
- Убедитесь, что email добавлен в Test users в OAuth consent screen
- Опубликуйте приложение (Publishing status: In production)

## Полезные ссылки

- [Google OAuth 2.0 Documentation](https://developers.google.com/identity/protocols/oauth2)
- [Google Cloud Console](https://console.cloud.google.com/)
- [OAuth 2.0 Playground](https://developers.google.com/oauthplayground/)

