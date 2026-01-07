# Инвентаризация файлов: Portal Login → Forum Entry

## Таблица файлов, участвующих в процессе логина Portal → вход в Forum

| Файл | Путь | Роль | Кто вызывает | Где используется | Примечание |
|------|------|------|--------------|-------------------|------------|
| **1. Frontend Login Form** |
| `script.js` | `auth/login/script.js` | Обработчик формы логина на клиенте | Браузер (DOM событие submit) | `auth/login/index.html` | Отправляет POST запрос с email/password в JSON |
| `index.html` | `auth/login/index.html` | HTML форма логина | Браузер | Прямой доступ по URL | Содержит форму с полями email/password |
| **2. Portal Login Endpoint** |
| `login.php` | `app/login.php` | Основной endpoint логина Portal | `auth/login/script.js` (fetch POST) | Клиентский JS | Проверяет credentials, создает сессию Portal |
| `auth_helpers.php` | `php/auth_helpers.php` | Универсальная функция создания сессии | `app/login.php` (строка 127) | `app/login.php`, `app/register.php` | Функция `portal_login_success()` - создает сессию в БД и PHP session |
| `db.php` | `config/db.php` | Конфигурация БД Portal | `app/login.php` (строка 54) | Все PHP файлы Portal | Environment-aware (local/prod) |
| **3. Portal Entry API** |
| `portal_entry.php` | `app/api/portal_entry.php` | API endpoint для входа в Forum | `auth/login/script.js` (fetch GET после успешного логина) | Клиентский JS | Проверяет сессию Portal, создает/находит forum user, вызывает XenForo |
| `bootstrap.php` | `app/bootstrap.php` | **ОТСУТСТВУЕТ** | `app/api/portal_entry.php` (строка 8) | `app/api/portal_entry.php` | **ФАЙЛ НЕ НАЙДЕН - требуется создание** |
| `sso_config.php` | `config/sso/sso_config.php` | Конфигурация SSO (shared secret, URLs) | `app/api/portal_entry.php` (строка 73) | `app/api/portal_entry.php` | Содержит shared_secret и xf_base URL |
| **4. Portal Database Functions** |
| `portal_entry.php` (helpers) | `app/api/portal_entry.php` (строки 150-280) | Вспомогательные функции для работы с БД | Внутри `app/api/portal_entry.php` | `app/api/portal_entry.php` | `buildPortalPdo()`, `fetchPortalUser()`, `lookupForumUserId()`, `upsertForumLink()`, `provisionForumUser()` |
| **5. XenForo PortalEntry Addon** |
| `Entry.php` | `forum/src/addons/Sinbad/PortalEntry/Pub/Controller/Entry.php` | Контроллер XenForo для входа через Portal | `app/api/portal_entry.php` (curl POST) | XenForo routing system | Обрабатывает запрос от Portal, проверяет secret, вызывает `setupUser()` |
| `Setup.php` | `forum/src/addons/Sinbad/PortalEntry/Setup.php` | Установщик аддона PortalEntry | XenForo при установке/обновлении аддона | XenForo addon system | Регистрирует маршрут `portal-entry` |
| `addon.json` | `forum/src/addons/Sinbad/PortalEntry/addon.json` | Метаданные аддона PortalEntry | XenForo addon system | XenForo addon system | Определяет аддон Sinbad/PortalEntry |
| `config.php` | `forum/src/config.php` | Конфигурация XenForo | XenForo framework | Все XenForo компоненты | Содержит `portalEntry.shared_secret` (строка 15) |
| **6. XenForo Core Login System** |
| `LoginPlugin.php` | `forum/src/XF/ControllerPlugin/LoginPlugin.php` | Плагин для логина в XenForo | `forum/src/addons/Sinbad/PortalEntry/Pub/Controller/Entry.php` (непрямо через setupUser) | XenForo login controllers | Метод `completeLogin()` - завершает процесс логина |
| `App.php` | `forum/src/XF/App.php` | Основной класс приложения XenForo | XenForo framework | Все XenForo компоненты | Методы `setup()`, `getVisitorFromSession()` |
| `Pub/App.php` | `forum/src/XF/Pub/App.php` | Публичное приложение XenForo | XenForo framework | Публичные контроллеры | Методы `onSessionCreation()`, `loginFromRememberCookie()` |
| `Session.php` | `forum/src/XF/Session/Session.php` | Класс сессии XenForo | XenForo framework | Все XenForo компоненты | Управление сессиями пользователей |
| **7. Database Tables** |
| `users` | Portal DB | Таблица пользователей Portal | Все Portal PHP файлы | `app/login.php`, `app/api/portal_entry.php` | Хранит данные пользователей Portal |
| `sessions` | Portal DB | Таблица сессий Portal | `php/auth_helpers.php` | `php/auth_helpers.php` | Хранит токены сессий Portal |
| `portal_forum_link` | Portal DB | Связь Portal users с Forum users | `app/api/portal_entry.php` | `app/api/portal_entry.php` | Связывает `public_id` Portal с `forum_user_id` XenForo |
| `xf_user` | Forum DB | Таблица пользователей XenForo | XenForo framework | XenForo core | Хранит данные пользователей форума |
| `xf_session` | Forum DB | Таблица сессий XenForo | XenForo framework | XenForo core | Хранит сессии XenForo |
| **8. Configuration Files** |
| `db.local.php` | `config/db.local.php` | Локальная конфигурация БД | `config/db.php` | `config/db.php` | Используется на localhost |
| `db.prod.php` | `config/db.prod.php` | Продакшн конфигурация БД | `config/db.php` | `config/db.php` | Используется на продакшн сервере |

## Поток выполнения логина

1. **Клиент**: Пользователь заполняет форму в `auth/login/index.html`
2. **Клиент**: `auth/login/script.js` отправляет POST `/app/login.php` с JSON `{email, password}`
3. **Portal**: `app/login.php` проверяет credentials, вызывает `portal_login_success()` из `php/auth_helpers.php`
4. **Portal**: `portal_login_success()` создает запись в таблице `sessions`, устанавливает cookie и PHP session
5. **Клиент**: После успешного логина `auth/login/script.js` отправляет GET `/app/api/portal_entry.php`
6. **Portal**: `app/api/portal_entry.php` проверяет сессию, находит/создает forum user через `provisionForumUser()`
7. **Portal**: `app/api/portal_entry.php` отправляет curl POST на XenForo `/forum/index.php?r=portal-entry` с `forum_user_id` и заголовком `X-Portal-Secret`
8. **XenForo**: `forum/src/addons/Sinbad/PortalEntry/Pub/Controller/Entry.php` проверяет secret, находит user, вызывает `\XF::visitor()->setupUser($user)`
9. **XenForo**: `setupUser()` устанавливает сессию XenForo, пользователь авторизован
10. **Клиент**: Получает `redirect_url` и перенаправляется на форум

## Критические зависимости

- **Отсутствует**: `app/bootstrap.php` - требуется для `app/api/portal_entry.php`
- **Shared Secret**: Должен совпадать в `config/sso/sso_config.php` и `forum/src/config.php`
- **Database**: Portal и Forum используют разные БД, связь через `portal_forum_link` таблицу


