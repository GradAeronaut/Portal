# Аудит соответствия кода каноническому потоку логина

## Легенда статусов

- **OK** — соответствует канону, необходим для работы потока
- **Лишний** — не нужен канону, не используется в потоке
- **Опасный** — вмешивается в поток, дублирует логику, может обойти канон
- **Недостающий** — требуется каноном, но отсутствует

---

## 1. Frontend Login Form

| Файл | Путь | Статус | Обоснование |
|------|------|--------|-------------|
| `script.js` | `auth/login/script.js` | **OK** | Соответствует канону: отправляет POST `/app/login.php`, затем GET `/app/api/portal_entry.php`, перенаправляет на Forum |
| `index.html` | `auth/login/index.html` | **OK** | Соответствует канону: содержит форму логина, используется JS для обработки |

---

## 2. Portal Login Endpoint

| Файл | Путь | Статус | Обоснование |
|------|------|--------|-------------|
| `login.php` | `app/login.php` | **OK** | Соответствует канону: проверяет credentials, вызывает `portal_login_success()`, возвращает `{ok: true}` |
| `auth_helpers.php` | `php/auth_helpers.php` | **OK** | Соответствует канону: функция `portal_login_success()` создает сессию Portal (БД, cookie, PHP session) |
| `db.php` | `config/db.php` | **OK** | Соответствует канону: конфигурация БД Portal, необходима для всех операций с БД |

---

## 3. Portal Entry API

| Файл | Путь | Статус | Обоснование |
|------|------|--------|-------------|
| `portal_entry.php` | `app/api/portal_entry.php` | **OK** | Соответствует канону: проверяет сессию Portal, создает/находит forum user, вызывает XenForo через PortalEntry |
| `bootstrap.php` | `app/bootstrap.php` | **Недостающий** | Требуется каноном: используется в `portal_entry.php` (строка 8), но файл отсутствует |
| `sso_config.php` | `config/sso/sso_config.php` | **OK** | Соответствует канону: содержит shared_secret и xf_base URL, необходим для вызова XenForo |

---

## 4. Portal Database Functions

| Файл | Путь | Статус | Обоснование |
|------|------|--------|-------------|
| `portal_entry.php` (helpers) | `app/api/portal_entry.php` (строки 150-280) | **OK** | Соответствует канону: вспомогательные функции (`buildPortalPdo()`, `fetchPortalUser()`, `lookupForumUserId()`, `upsertForumLink()`, `provisionForumUser()`) необходимы для работы PortalEntry |

---

## 5. XenForo PortalEntry Addon

| Файл | Путь | Статус | Обоснование |
|------|------|--------|-------------|
| `Entry.php` | `forum/src/addons/Sinbad/PortalEntry/Pub/Controller/Entry.php` | **OK** | Соответствует канону: единственный допустимый способ входа в Forum, проверяет secret, вызывает `setupUser()` |
| `Setup.php` | `forum/src/addons/Sinbad/PortalEntry/Setup.php` | **OK** | Соответствует канону: регистрирует маршрут `portal-entry`, необходим для работы аддона |
| `addon.json` | `forum/src/addons/Sinbad/PortalEntry/addon.json` | **OK** | Соответствует канону: метаданные аддона, необходимы для установки аддона в XenForo |
| `config.php` | `forum/src/config.php` | **OK** | Соответствует канону: содержит `portalEntry.shared_secret`, необходим для проверки запросов от Portal |

---

## 6. XenForo Core Login System

| Файл | Путь | Статус | Обоснование |
|------|------|--------|-------------|
| `LoginPlugin.php` | `forum/src/XF/ControllerPlugin/LoginPlugin.php` | **Опасный** | Содержит методы прямого логина (`completeLogin()`, `actionPasswordConfirm()`), но используется через `setupUser()` в PortalEntry. Риск: может быть вызван напрямую, обходя Portal |
| `App.php` | `forum/src/XF/App.php` | **OK** | Соответствует канону: базовый класс XenForo, используется для `setupUser()` через PortalEntry |
| `Pub/App.php` | `forum/src/XF/Pub/App.php` | **Опасный** | Содержит `loginFromRememberCookie()` — автоматический логин через cookie, может обойти Portal. Риск: пользователь может войти в Forum без прохождения через Portal |
| `Session.php` | `forum/src/XF/Session/Session.php` | **OK** | Соответствует канону: управление сессиями XenForo, используется через `setupUser()` в PortalEntry |

---

## 7. XenForo Login Controllers (не в инвентаризации, но критично)

| Файл | Путь | Статус | Обоснование |
|------|------|--------|-------------|
| `LoginController.php` (Pub) | `forum/src/XF/Pub/Controller/LoginController.php` | **Опасный** | Прямой логин через `/forum/login` — запрещен каноном. Содержит `actionLogin()` с проверкой credentials, обходит Portal |
| `LoginController.php` (Admin) | `forum/src/XF/Admin/Controller/LoginController.php` | **Лишний** | Логин в админ-панель — не относится к публичному потоку, но может быть использован для обхода Portal (если доступен публично) |
| `LoginService.php` | `forum/src/XF/Service/User/LoginService.php` | **Опасный** | Сервис валидации логина/пароля — используется `LoginController`, может обойти Portal |

---

## 8. Database Tables

| Таблица | БД | Статус | Обоснование |
|---------|----|--------|-------------|
| `users` | Portal DB | **OK** | Соответствует канону: хранит каноническую идентичность пользователей Portal |
| `sessions` | Portal DB | **OK** | Соответствует канону: хранит сессии Portal, необходима для `portal_login_success()` |
| `portal_forum_link` | Portal DB | **OK** | Соответствует канону: связь Portal ↔ Forum, необходима для PortalEntry |
| `xf_user` | Forum DB | **OK** | Соответствует канону: хранит пользователей XenForo, создается через `provisionForumUser()` |
| `xf_session` | Forum DB | **OK** | Соответствует канону: хранит сессии XenForo, устанавливается через `setupUser()` |

---

## 9. Configuration Files

| Файл | Путь | Статус | Обоснование |
|------|------|--------|-------------|
| `db.local.php` | `config/db.local.php` | **OK** | Соответствует канону: локальная конфигурация БД, используется через `db.php` |
| `db.prod.php` | `config/db.prod.php` | **OK** | Соответствует канону: продакшн конфигурация БД, используется через `db.php` |

---

## Итоговая сводка

### Статусы по категориям

- **OK**: 20 файлов/таблиц
- **Опасный**: 4 файла
- **Недостающий**: 1 файл
- **Лишний**: 1 файл (Admin LoginController, условно)

### Критические проблемы

1. **Недостающий файл:**
   - `app/bootstrap.php` — требуется для `app/api/portal_entry.php`, отсутствует

2. **Опасные файлы (могут обойти канон):**
   - `forum/src/XF/Pub/Controller/LoginController.php` — прямой логин в Forum, запрещен каноном
   - `forum/src/XF/Pub/App.php` — автоматический логин через cookie, может обойти Portal
   - `forum/src/XF/ControllerPlugin/LoginPlugin.php` — методы прямого логина, могут быть вызваны напрямую
   - `forum/src/XF/Service/User/LoginService.php` — сервис валидации логина, используется прямым логином

### Рекомендации

1. **Создать** `app/bootstrap.php` для `app/api/portal_entry.php`
2. **Отключить/заблокировать** прямой логин через `/forum/login` (запрещено каноном)
3. **Отключить** автоматический логин через cookie в `Pub/App.php` (или ограничить только для пользователей, вошедших через Portal)
4. **Заблокировать** публичный доступ к `LoginController` (Pub) — единственный вход через PortalEntry
5. **Проверить** доступность Admin LoginController публично — должен быть доступен только для админов

---

## Примечания

- Файлы XenForo Core (`App.php`, `Session.php`) используются через `setupUser()` в PortalEntry, что соответствует канону
- `LoginPlugin.php` используется косвенно через `setupUser()`, но содержит опасные методы прямого логина
- Прямые контроллеры логина Forum должны быть отключены/заблокированы согласно канону


