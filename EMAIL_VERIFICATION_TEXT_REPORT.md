# Отчёт: Поиск текстов писем подтверждения email

## Шаг 1. Результаты поиска

### ✅ НАЙДЕНО: Тексты писем в XenForo форуме

**Расположение:** `forum/src/addons/XF/_data/`

#### 1. Шаблон email: `user_email_confirmation`
**Файл:** `forum/src/addons/XF/_data/templates.xml` (строки 28694-28709)

```xml
<template type="email" title="user_email_confirmation" version_id="2021670" version_string="2.2.16">
<mail:subject>
	{{ phrase('user_email_confirmation_subject', {
		'boardTitle': $xf.options.boardTitle
	}) }}
</mail:subject>

{{ phrase('user_email_confirmation_body_html', {
	'username': $user.username,
	'board': '<a href="' . link('canonical:index') . '">' . $xf.options.boardTitle . '</a>'
}) }}

<p><a href="{{ link('canonical:account-confirmation/email', $user, {'c': $confirmation.confirmation_key}) }}" class="button">{{ phrase('confirm_your_email') }}</a></p>

{{ phrase('user_email_confirmation_plain_link_html', {
	'link': link('canonical:account-confirmation/email', $user, {'c': $confirmation.confirmation_key})
}) }}
</template>
```

#### 2. Фразы (тексты) письма
**Файл:** `forum/src/addons/XF/_data/phrases.xml` (строки 10676-10678)

**Тема письма:**
```
{boardTitle} - Account confirmation required
```
*Фраза:* `user_email_confirmation_subject`

**Тело письма (HTML):**
```
<p>{username}, in order to complete your registration or reactivate your account at {board}, you need to confirm your email address by clicking the button below.</p>
```
*Фраза:* `user_email_confirmation_body_html`

**Альтернативная ссылка (текст):**
```
<p class="minorText">Or, paste the following link into your browser: {link}</p>
```
*Фраза:* `user_email_confirmation_plain_link_html`

#### 3. Система отправки в XenForo
**Файлы:**
- `forum/src/XF/Service/User/EmailConfirmationService.php` - сервис подтверждения email
- `forum/src/XF/Service/User/AbstractConfirmationService.php` - базовый класс (метод `sendConfirmationEmail()`)
- `forum/src/XF/Entity/UserConfirmation.php` - сущность подтверждения

**Метод отправки:**
```php
protected function sendConfirmationEmail()
{
    $mail = $this->app->mailer()->newMail();
    $mail->setToUser($this->user)
        ->setTemplate($this->getEmailTemplateName(), $this->getEmailTemplateParams());
    $mail->send();
}
```

**Используемый шаблон:** `user_email_confirmation` (определяется в `getEmailTemplateName()`)

---

### ❌ НЕ НАЙДЕНО: Тексты писем в портале (app/)

**Проверенные файлы:**
- `app/register.php` - регистрация (нет отправки email, нет текстов)
- `app/verify.php` - обработка верификации (только обработчик, не отправка)
- `php/auth_helpers.php` - хелперы авторизации (нет email-функций)
- `config/` - нет конфигурации email/SMTP

**Результат:**
- ❌ Нет генерации `verification_token` при регистрации
- ❌ Нет отправки email
- ❌ Нет текстов писем
- ❌ Нет констант с текстами
- ❌ Нет шаблонов писем

**Примечание:** Согласно `EMAIL_DIAGNOSIS_REPORT.md`, в портале полностью отсутствует функционал отправки email при регистрации.

---

## Шаг 2. Анализ и выводы

### 2.1. Найден ли существующий текст?

**✅ ДА, найден в XenForo форуме:**
- Полный шаблон письма подтверждения
- Все необходимые фразы (тема, тело, альтернативная ссылка)
- Готовая система отправки через Symfony Mailer

**❌ НЕТ в портале:**
- Тексты писем отсутствуют
- Функционал отправки не реализован

---

### 2.2. Совпадает ли по смыслу?

**✅ ДА, полностью совпадает:**

Текст XenForo:
> "{username}, in order to complete your registration or reactivate your account at {board}, you need to confirm your email address by clicking the button below."

**Смысл:**
- Приветствие пользователя по имени
- Объяснение необходимости подтверждения
- Указание на кнопку/ссылку для подтверждения
- Альтернативная текстовая ссылка

**Это именно то, что нужно для портала:**
- Подтверждение email при регистрации
- Ссылка с токеном верификации
- Профессиональный тон

---

### 2.3. Можно ли переиспользовать без создания новой сущности?

**✅ ДА, можно переиспользовать:**

#### Вариант 1: Прямое использование (рекомендуется)
**Преимущества:**
- Текст уже готов и протестирован
- Единый стиль с форумом
- Не нужно создавать новую сущность

**Как использовать:**
1. Скопировать фразы из XenForo в портал
2. Адаптировать под структуру портала (заменить переменные)
3. Использовать тот же шаблон или создать упрощённую версию

**Адаптация текста:**
```
Оригинал (XenForo):
"{username}, in order to complete your registration or reactivate your account at {board}, you need to confirm your email address by clicking the button below."

Адаптация для портала:
"{username}, для завершения регистрации на {siteName} вам необходимо подтвердить ваш email адрес, нажав на кнопку ниже."

Или на английском (если портал англоязычный):
"{username}, in order to complete your registration at {siteName}, you need to confirm your email address by clicking the button below."
```

#### Вариант 2: Создание констант/конфига
**Не рекомендуется**, так как:
- Дублирует существующий функционал
- Усложняет поддержку
- Текст уже есть в XenForo

#### Вариант 3: Общий модуль email
**Можно рассмотреть**, если:
- Планируется много разных типов писем
- Нужна единая система шаблонов
- Требуется централизованное управление

---

## Рекомендации

### ✅ Рекомендуемое решение:

1. **Использовать текст из XenForo** как основу
2. **Адаптировать под портал:**
   - Заменить `{board}` на `{siteName}` или название портала
   - Упростить структуру (убрать зависимости от XenForo)
   - Сохранить смысл и тон

3. **Создать простой шаблон** в портале:
   ```php
   // Пример структуры
   $emailSubject = "Sinbad Portal - Account confirmation required";
   $emailBody = "
   <p>{$username}, in order to complete your registration at Sinbad Portal, 
   you need to confirm your email address by clicking the button below.</p>
   <p><a href=\"{$verificationLink}\" class=\"button\">Confirm your email</a></p>
   <p class=\"minorText\">Or, paste the following link into your browser: {$verificationLink}</p>
   ";
   ```

4. **Не создавать новую сущность** - использовать простые строки или константы в PHP

---

## Итоговый ответ

| Вопрос | Ответ |
|--------|-------|
| **Найден ли существующий текст?** | ✅ ДА, в XenForo форуме (`forum/src/addons/XF/_data/phrases.xml` и `templates.xml`) |
| **Где именно?** | `forum/src/addons/XF/_data/phrases.xml` (строки 10676-10678)<br>`forum/src/addons/XF/_data/templates.xml` (строки 28694-28709) |
| **Совпадает ли по смыслу?** | ✅ ДА, полностью совпадает - это именно письмо подтверждения регистрации |
| **Можно ли переиспользовать?** | ✅ ДА, рекомендуется переиспользовать текст, адаптировав под портал |
| **Нужна ли новая сущность?** | ❌ НЕТ, достаточно простых строк/констант в PHP |

---

**Дата создания отчёта:** $(date)
**Проверенные файлы:** 312 файлов с упоминанием verify/confirm/email
**Найдено текстов:** 3 фразы + 1 шаблон в XenForo, 0 в портале



