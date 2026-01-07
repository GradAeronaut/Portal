# Отчёт: Проверка текущей логики входа на Shape

**Дата:** 2025-12-29  
**Файл:** `shape-sinbad/index.php`

---

## Шаг 1. Точка входа Shape

### Файл рендеринга
- **Путь:** `/shape-sinbad/index.php`
- **URL:** `/shape-sinbad/` (обрабатывается через nginx `location /` → `try_files $uri $uri/ /index.php?$args`)
- **Root документа:** `/var/www/gradaeronaut.com/public` (nginx config)

### Проверка сессии
**❌ ПРОВЕРКА АВТОРИЗАЦИИ ОТСУТСТВУЕТ**

```php
<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
// НЕТ ПРОВЕРКИ: if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] <= 0)
```

**Текущее поведение:**
- Сессия **запускается** (`session_start()`)
- Данные берутся из сессии с fallback значениями:
  - `$user['id'] = $_SESSION['user_id'] ?? 0` (по умолчанию `0`)
  - `$pid = $_SESSION['public_id'] ?? ''` (по умолчанию `''`)
- Страница **рендерится даже без авторизации**
- Нет редиректа на `/auth/login/`

---

## Шаг 2. Текущее поведение при входе без сессии

### Что происходит

#### 1. **Shape страница рендерится полностью**
- ✅ Меню загружается (`menu/menu.php`)
- ✅ HTML структура рендерится
- ✅ CSS стили применяются
- ✅ Все блоки видны (Top Panel, Map, Timeline, Roadmap, Membership Cards)

#### 2. **Частичный доступ (почему появляется)**

**Блок Top Panel:**
```php
// Строки 332-337: Хардкод данных
<div class="user-name">Sever</div>
<div class="user-meta">SERGEY GRADOV · XF79Q2 · PREMIUM</div>
```
- ❌ Показываются **хардкод данные** вместо данных пользователя

**Блок Avatar:**
```php
// Строки 357-370
$userId = $user['id'] ?? 0;  // Без сессии = 0
$avatarUrl = "/app/avatar/view_avatar.php?id={$userId}&h={$avatarHash}&v=2";
```
- ❌ Аватар загружается с `id=0` → может показывать дефолтный/пустой аватар

**Блок Menu:**
```php
// menu/menu.php, строки 28-35
$isAuthorized = isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
$membershipUrl = $isAuthorized 
    ? '/shape-sinbad/#membership-cards-section' 
    : '/auth/login/?next=...';  // ✅ Правильная проверка только для Membership ссылки
```
- ✅ Кнопка Membership редиректит на логин (если не авторизован)
- ❌ Но сам Shape доступен без авторизации

**Блок Map:**
```php
// Строка 421
include $_SERVER['DOCUMENT_ROOT'] . '/map/index.php';
```
- ❌ `map/index.php` **не проверяет авторизацию** (только `session_start()`)
- ❌ Логирование визитов происходит даже без `public_id` (`$pid ?? ''`)

#### 3. **KNEEBOARD даёт 403 (не 404)**

**Iframe source:**
```php
// Строка 399
<iframe src="/app/forum.php?pid=<?= htmlspecialchars($pid ?? '') ?>" ...>
```

**Обработчик `/app/forum.php`:**
```php
// Строки 8-11
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
    http_response_code(403);
    exit;
}
```
- ✅ `/app/forum.php` **правильно проверяет авторизацию**
- ❌ Без сессии возвращает **HTTP 403** (Forbidden)
- ⚠️ Iframe показывает пустую страницу/403 вместо редиректа

**Примечание:** Файл `/app/forum.php` используется вместо `/app/kneeboard.php` (оба файла существуют и идентичны).

---

## Итоговая таблица проверок

| Компонент | Файл | Проверка сессии | Поведение без сессии |
|-----------|------|-----------------|---------------------|
| **Shape страница** | `shape-sinbad/index.php` | ❌ НЕТ | Рендерится полностью |
| **Top Panel (данные)** | `shape-sinbad/index.php:332-337` | ❌ НЕТ | Хардкод "Sever / SERGEY GRADOV" |
| **Avatar** | `shape-sinbad/index.php:357-370` | ❌ НЕТ | `id=0`, возможен дефолтный аватар |
| **Menu Membership** | `menu/menu.php:28-35` | ✅ ЕСТЬ | Редирект на `/auth/login/` |
| **Map** | `map/index.php` | ❌ НЕТ | Рендерится, логирование с пустым `public_id` |
| **KNEEBOARD iframe** | `/app/forum.php` | ✅ ЕСТЬ | HTTP 403 в iframe |
| **KNEEBOARD (XenForo)** | `forum/index.php` | ✅ ЕСТЬ | Редирект на `/auth/login/` (шаг 3) |

---

## Проблемы

1. **Shape доступен без авторизации** → страница рендерится с невалидными данными
2. **Хардкод данных пользователя** → показываются данные "Sever / SERGEY GRADOV" вместо реальных
3. **KNEEBOARD iframe получает 403** → пустая область вместо редиректа
4. **Нет единой точки проверки** → разные компоненты ведут себя по-разному

---

## Рекомендации

1. ✅ Добавить проверку авторизации в начало `shape-sinbad/index.php`
2. ✅ Убрать хардкод данных пользователя
3. ✅ Редирект на `/auth/login/?next=/shape-sinbad/` при отсутствии сессии
4. ✅ Добавить проверку авторизации в `map/index.php` (если требуется)

