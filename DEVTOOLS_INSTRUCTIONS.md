# ИНСТРУКЦИЯ: Где найти элементы в DevTools

## 1. ЛЕВЫЙ БЛОК (пользователь)

**В HTML структуре:**
1. Разверните `<section class="shape-top-panel">`
2. Внутри найдите `<div class="banner-inner">`
3. Внутри найдите `<div class="left-block">`
4. Разверните `.left-block`

**Что должно быть внутри:**
```html
<div class="left-block">
    <div class="full-name">Sergey Gradov</div>
    <div class="user-info-line">ID · PREMIUM</div>
</div>
```

**Что НЕ должно быть:**
- `<div class="public-name">`
- `<div class="public-id">`
- `<div class="level">`

**Проверка стилей:**
- Выделите `.user-info-line`
- В Styles должно быть: `font-size: 14px`, `opacity: 0.7`

---

## 2. ПРАВЫЙ БЛОК (аватар + прибор)

**В HTML структуре:**
1. Внутри `<section class="shape-top-panel">`
2. Найдите `<div class="right-block">` (он на том же уровне, что и `.banner-inner`)

**Проверка стилей:**
- Выделите `.right-block`
- В Computed Styles проверьте:
  - `position: absolute`
  - `bottom: 15px` (НЕ 20px!)
  - `right: 20px`

**Внутри `.right-block` должно быть:**
- `<div class="fuel-button">` (прибор)
- `<div>` с аватаром (wrapper)

---

## 3. ПРИБОР (fuel-button)

**В HTML:**
- Внутри `.right-block` найдите `<div class="fuel-button">`
- Внутри должен быть `<img class="balance-card">`

**Проверка размеров:**
- Выделите `<img class="balance-card">`
- В Computed Styles должно быть:
  - `width: 90px` (НЕ 112px!)
  - `height: 90px` (НЕ 112px!)

---

## 4. АВАТАР

**В HTML:**
- Внутри `.right-block` найдите `<div>` (wrapper, не `.fuel-button`)
- Внутри должен быть либо:
  - `<img class="avatar-image">` (если есть загруженный аватар)
  - Или аватар генерируется через PHP

**Проверка размеров:**
- Найдите элемент аватара
- В Computed Styles должно быть:
  - `width: 90px` (НЕ 112px или 140px!)
  - `height: 90px`

**Проверка fallback аватара:**
- Если есть `<div class="avatar-fallback">`:
  - `background-color: #4A5568` (серо-графитовый, НЕ фиолетовый!)
  - `font-size: 80px` (большая буква)

---

## БЫСТРАЯ ПРОВЕРКА:

1. **Левый блок:** `.shape-top-panel > .banner-inner > .left-block`
2. **Правый блок:** `.shape-top-panel > .right-block`
3. **Прибор:** `.right-block > .fuel-button > .balance-card`
4. **Аватар:** `.right-block > div:not(.fuel-button) > .avatar-image` или `.avatar-fallback`

---

## ЧТО ИСКАТЬ В STYLES ПАНЕЛИ:

Для каждого элемента проверьте:
- **Левый блок:** `.banner-inner .user-info-line` - должен быть `font-size: 14px`, `opacity: 0.7`
- **Правый блок:** `.shape-top-panel .right-block` - должен быть `bottom: 15px`
- **Прибор/Аватар:** размеры должны быть `90px`, не `112px` или `140px`





