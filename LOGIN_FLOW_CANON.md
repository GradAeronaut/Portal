# Канонический поток логина: Portal → Forum

## Принцип

**Portal владеет идентичностью. Forum пассивен.**  
Единственный допустимый способ входа в Forum — через Portal. Прямой логин в Forum запрещен.

---

## Канонический поток (единственный допустимый)

### Шаг 1: Клиент → Portal Login
**Исполнитель:** JS (`auth/login/script.js`)  
**Действие:** POST запрос на `/app/login.php`  
**Данные:** JSON `{email: string, password: string}`  
**Headers:** `Content-Type: application/json`

### Шаг 2: Portal проверяет credentials
**Исполнитель:** Portal PHP (`app/login.php`)  
**Действия:**
- Валидация email формата
- Поиск пользователя в таблице `users` Portal DB
- Проверка пароля через `password_verify()`
- Проверка статуса пользователя (`pending_sso`, `email_verified`)
- При успехе: вызов `portal_login_success()` из `php/auth_helpers.php`

### Шаг 3: Portal создает сессию
**Исполнитель:** Portal PHP (`php/auth_helpers.php::portal_login_success()`)  
**Действия:**
- Генерация токена сессии (64 hex символа)
- Создание записи в таблице `sessions` Portal DB
- Установка cookie `session_token`
- Заполнение PHP `$_SESSION` (user_id, public_id, username, email, etc.)
- Возврат `true` в `app/login.php`

### Шаг 4: Portal возвращает успех
**Исполнитель:** Portal PHP (`app/login.php`)  
**Действие:** JSON ответ `{ok: true}`

### Шаг 5: Клиент запрашивает вход в Forum
**Исполнитель:** JS (`auth/login/script.js`)  
**Действие:** GET запрос на `/app/api/portal_entry.php`  
**Headers:** Cookie с `session_token` (credentials: same-origin)

### Шаг 6: Portal проверяет сессию и готовит Forum user
**Исполнитель:** Portal PHP (`app/api/portal_entry.php`)  
**Действия:**
- Проверка `$_SESSION['user_id']` (если нет → 403)
- Загрузка данных пользователя Portal по `user_id`
- Проверка наличия `public_id`
- Поиск `forum_user_id` в таблице `portal_forum_link` по `public_id`
- Если `forum_user_id` не найден: вызов `provisionForumUser()` для создания пользователя в XenForo
- Сохранение связи `public_id` ↔ `forum_user_id` в `portal_forum_link`

### Шаг 7: Portal вызывает XenForo
**Исполнитель:** Portal PHP (`app/api/portal_entry.php`)  
**Действие:** curl POST на `/forum/index.php?r=portal-entry`  
**Данные:** `forum_user_id=<id>&public_id=<id>` (POST body)  
**Headers:** `X-Portal-Secret: <shared_secret>`

### Шаг 8: XenForo проверяет и устанавливает сессию
**Исполнитель:** XenForo (`forum/src/addons/Sinbad/PortalEntry/Pub/Controller/Entry.php`)  
**Действия:**
- Проверка заголовка `X-Portal-Secret` (должен совпадать с `config['portalEntry']['shared_secret']`)
- Поиск пользователя XenForo по `forum_user_id`
- Вызов `\XF::visitor()->setupUser($user)` для установки сессии XenForo
- Сохранение сессии через `\XF::session()->save()`
- Возврат редиректа на главную форума

### Шаг 9: Portal возвращает redirect URL
**Исполнитель:** Portal PHP (`app/api/portal_entry.php`)  
**Действие:** JSON ответ `{success: true, redirect_url: "https://gradaeronaut.com/forum/"}`

### Шаг 10: Клиент перенаправляется на Forum
**Исполнитель:** JS (`auth/login/script.js`)  
**Действие:** `window.location.href = redirect_url`

---

## Границы ответственности

### JavaScript (Frontend)
**Ответственность:**
- ✅ Отображение формы логина
- ✅ Валидация полей формы (email формат, обязательные поля)
- ✅ Отправка POST `/app/login.php` с credentials
- ✅ Обработка ответов от Portal (успех/ошибки)
- ✅ Отправка GET `/app/api/portal_entry.php` после успешного логина
- ✅ Перенаправление на Forum по полученному `redirect_url`
- ✅ Отображение ошибок пользователю

**Запрещено:**
- ❌ Прямые запросы к Forum endpoints
- ❌ Управление сессиями напрямую
- ❌ Хранение паролей или токенов в localStorage/sessionStorage
- ❌ Логика авторизации (только UI)

### Portal PHP (Backend)
**Ответственность:**
- ✅ Валидация и проверка credentials пользователей Portal
- ✅ Создание и управление сессиями Portal (таблица `sessions`, cookie, PHP session)
- ✅ Управление связью Portal ↔ Forum (таблица `portal_forum_link`)
- ✅ Создание пользователей в XenForo при первом входе (`provisionForumUser()`)
- ✅ Вызов XenForo через PortalEntry endpoint
- ✅ Владеет канонической идентичностью пользователя (таблица `users` Portal DB)

**Запрещено:**
- ❌ Прямое управление сессиями XenForo (только через PortalEntry)
- ❌ Изменение данных пользователей XenForo напрямую (только создание при первом входе)
- ❌ Альтернативные способы входа в Forum (только через PortalEntry)

### Forum/XenForo (Backend)
**Ответственность:**
- ✅ Прием запросов от Portal через PortalEntry endpoint
- ✅ Проверка shared secret (`X-Portal-Secret` header)
- ✅ Установка сессии XenForo через `setupUser()`
- ✅ Управление сессиями XenForo (таблица `xf_session`)

**Запрещено:**
- ❌ Прямой логин пользователей (только через PortalEntry)
- ❌ Регистрация новых пользователей (только через Portal)
- ❌ Восстановление пароля (только через Portal)
- ❌ Изменение email/username без синхронизации с Portal
- ❌ Создание пользователей напрямую (только через `provisionForumUser()` из Portal)

---

## Разрешенные операции

### Portal
- ✅ Логин через `/app/login.php`
- ✅ Регистрация через `/app/register.php`
- ✅ Восстановление пароля через Portal
- ✅ Верификация email через Portal
- ✅ Управление профилем пользователя Portal
- ✅ Создание связи Portal ↔ Forum через `portal_forum_link`
- ✅ Вызов XenForo PortalEntry endpoint

### Forum
- ✅ Отображение контента для авторизованных пользователей
- ✅ Управление форумными функциями (посты, темы, etc.)
- ✅ Прием входа через PortalEntry endpoint (единственный способ)
- ✅ Управление сессиями XenForo для пользователей, вошедших через Portal

### JavaScript
- ✅ Отправка запросов на Portal endpoints (`/app/login.php`, `/app/api/portal_entry.php`)
- ✅ Обработка ответов и отображение ошибок
- ✅ Перенаправление на Forum после успешного входа

---

## Запрещенные операции

### Portal
- ❌ Прямое изменение данных пользователей XenForo (кроме создания при первом входе)
- ❌ Прямое управление сессиями XenForo
- ❌ Альтернативные способы входа в Forum (OAuth, SSO токены, прямые API вызовы)

### Forum
- ❌ Прямой логин через `/forum/login` (должен быть отключен/заблокирован)
- ❌ Регистрация через `/forum/register` (должна быть отключена/заблокирована)
- ❌ Восстановление пароля через Forum
- ❌ Любые endpoints логина, кроме PortalEntry
- ❌ Создание пользователей напрямую (только через Portal `provisionForumUser()`)

### JavaScript
- ❌ Прямые запросы к Forum API endpoints
- ❌ Обход Portal при входе в Forum
- ❌ Хранение credentials или токенов в браузере

---

## Критические правила

1. **Единственный вход в Forum:** только через Portal → PortalEntry
2. **Portal владеет идентичностью:** все данные пользователя хранятся в Portal DB
3. **Forum пассивен:** Forum не создает пользователей, не обрабатывает логин напрямую
4. **Shared Secret обязателен:** все запросы Portal → Forum должны содержать валидный `X-Portal-Secret`
5. **Связь через `portal_forum_link`:** связь `public_id` (Portal) ↔ `forum_user_id` (XenForo) хранится только в Portal DB
6. **Сессии разделены:** Portal и Forum имеют независимые системы сессий, синхронизируются только через PortalEntry

---

## Исключения и особые случаи

**Нет.** Канонический поток — единственный допустимый способ входа.  
Любые отклонения от этого потока считаются нарушением архитектуры и должны быть исправлены.

---

## Валидация канона

При добавлении нового кода или изменении существующего, проверьте:

- [ ] Соответствует ли код каноническому потоку?
- [ ] Не нарушает ли код границы ответственности?
- [ ] Не добавляет ли код запрещенные операции?
- [ ] Сохраняется ли принцип "Portal владеет идентичностью"?

Если хотя бы один ответ "нет" — код должен быть переработан.


