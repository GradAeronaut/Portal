# Отчёт: Поиск обхода PHP-gate Shape

**Дата:** 2025-12-29  
**Цель:** Проверка реального ответа сервера и поиск client-side обходов gate

---

## Шаг 1. Реальный ответ сервера

### Команда:
```bash
curl -I https://gradaeronaut.com/shape-sinbad/
```

### Результат:
```
HTTP/1.1 200 OK
Server: nginx/1.24.0 (Ubuntu)
Date: Mon, 29 Dec 2025 16:37:27 GMT
Content-Type: text/html; charset=UTF-8
Connection: keep-alive
Set-Cookie: PHPSESSID=n4dsgvv25oqp6qgbbmbj4frvqm; path=/
Expires: Thu, 19 Nov 1981 08:52:00 GMT
Cache-Control: no-store, no-cache, must-revalidate
Pragma: no-cache
X-Frame-Options: SAMEORIGIN
X-Content-Type-Options: nosniff
Referrer-Policy: no-referrer-when-downgrade
```

### ❌ ПРОБЛЕМА ОБНАРУЖЕНА

**Ожидалось:** HTTP 302 → редирект на `/auth/login/?next=/shape-sinbad/`  
**Фактически:** HTTP 200 OK — страница отдаётся без редиректа

**Вывод:** PHP gate **НЕ РАБОТАЕТ** на продакшн сервере. Страница отдаётся даже без авторизации.

---

## Шаг 2. Client-side обходы

### Найденные проблемы:

#### ❌ 1. Геолокация запрашивается ДО прохождения gate

**Файл:** `map/map.js` (подключается через `map/index.php`, включается в `shape-sinbad/index.php`)

**Код инициализации:**
```javascript
// Строка 332-333: Запрос геолокации по IP сразу при загрузке
const ip = await getIPCenter();
if (ip) center = ip;

// Функция getIPCenter() (строка 286):
const getIPCenter = async () => {
    const j = await fetchJSON("https://ipapi.co/json");
    return (j && j.latitude && j.longitude) ? {lat: j.latitude, lng: j.longitude} : null;
};

// Строка 341: Запрос точной геолокации через 3 секунды
delay(3000).then(async () => {
    const p = await getPrecise();
    // ...
});

// Функция getPrecise() (строка 290-297):
const getPrecise = (timeout = 6000) => new Promise(res => {
    if (!navigator.geolocation) return res(null);
    const t = setTimeout(() => res(null), timeout);
    navigator.geolocation.getCurrentPosition(
        p => {
            clearTimeout(t);
            res({lat: p.coords.latitude, lng: p.coords.longitude});
        },
        _ => { /* ошибка */ }
    );
});
```

**Проблема:**
- Геолокация запрашивается **сразу при загрузке** страницы
- Если PHP gate не срабатывает (HTTP 200), то:
  - `map/index.php` загружается (строка 421 в `shape-sinbad/index.php`)
  - `map.js` выполняется (строка 72 в `map/index.php`)
  - Google Maps инициализируется (строка 74 в `map/index.php`)
  - Запрос к `https://ipapi.co/json` выполняется **до** прохождения gate
  - Запрос `navigator.geolocation.getCurrentPosition()` выполняется **через 3 секунды**

**Статус:** ❌ **КРИТИЧЕСКАЯ ПРОБЛЕМА** — геолокация запрашивается даже без авторизации

#### ✅ 2. Прямые ссылки на `/shape-sinbad/`

**Файлы:**
- `menu/menu.php` (строка 25, 40): `<a href="/shape-sinbad/">Shape Sinbad</a>`
- `menu/menu.js` (строка 95): `window.location.href = \`/${page}/\`;`

**Статус:** ✅ **БЕЗ ПРОБЛЕМЫ** — ссылки ведут на `/shape-sinbad/`, который должен проверяться PHP gate

#### ✅ 3. Нет альтернативных путей загрузки

**Проверено:**
- Нет прямых ссылок на статические HTML файлы Shape
- Нет AJAX-загрузки контента Shape без прохождения gate
- Нет `innerHTML` / `document.write` для рендеринга Shape до PHP

**Статус:** ✅ **БЕЗ ПРОБЛЕМЫ**

---

## Итог

### Основная проблема:

❌ **PHP gate НЕ РАБОТАЕТ на продакшн сервере**
- Ожидалось: HTTP 302 редирект
- Фактически: HTTP 200 OK
- Страница отдаётся полностью без проверки сессии

### Дополнительные проблемы:

❌ **Геолокация запрашивается до прохождения gate**
- `map.js` инициализируется при загрузке страницы
- Запрос к `ipapi.co/json` выполняется сразу
- `navigator.geolocation.getCurrentPosition()` запрашивается через 3 секунды
- Проблема актуальна только если PHP gate не работает

### Рекомендации:

1. ✅ **СРОЧНО:** Проверить, почему PHP gate не работает на продакшн
   - Возможно, файл не обновлён на сервере
   - Возможно, `die('GATE_OK')` всё ещё активен
   - Возможно, код не выполняется (другая версия файла)

2. ⚠️ **Вторично:** Добавить проверку авторизации в `map/index.php`
   - Защита от запроса геолокации без авторизации
   - Если gate не сработает, map не загрузится

3. ✅ **Опционально:** Перенести инициализацию геолокации после подтверждения авторизации

---

**Вывод:** Основная проблема — PHP gate не работает на продакшн сервере. Геолокация запрашивается, но это вторичная проблема, так как при работающем gate страница не должна отдаваться.



