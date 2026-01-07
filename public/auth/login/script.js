console.log('LOGIN JS VERSION 857072c');

/* ============================
   УТИЛИТЫ
============================ */

const EYE_OPEN_SVG = `
    <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"></path>
        <circle cx="12" cy="12" r="3"></circle>
    </svg>
`;

const EYE_CLOSED_SVG = `
    <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M1 12s4-7 11-7c2.5 0 4.7.8 6.5 2"></path>
        <path d="M23 12s-4 7-11 7c-2.5 0-4.7-.8-6.5-2"></path>
        <line x1="3" y1="3" x2="21" y2="21"></line>
    </svg>
`;

/* Проверка email */
function isValidEmail(email) {
    return /^[^@]+@[^@]+\.[^@]+$/.test(email.trim());
}

/* Показ ошибки */
function setError(id, msg) {
    const el = document.getElementById(id);
    if (el) el.textContent = msg || "";
}

/* Показ ошибки с HTML (для ссылок) */
function setErrorHTML(id, html) {
    const el = document.getElementById(id);
    if (el) el.innerHTML = html || "";
}

/* Очистка ошибки */
function clearError(id) {
    const el = document.getElementById(id);
    if (el) el.textContent = "";
}

/* Парсинг параметра next из URL */
function getNextParam() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('next') || null;
}

/* ============================
   ГЛАЗИКИ
============================ */
function renderEyeIcon(toggle, open) {
    toggle.innerHTML = open ? EYE_OPEN_SVG : EYE_CLOSED_SVG;
    toggle.setAttribute("aria-pressed", open ? "true" : "false");
    toggle.setAttribute("aria-label", open ? "Hide password" : "Show password");
}

function initPasswordToggles() {
    document.querySelectorAll('.pass-toggle').forEach(toggle => {
        const targetId = toggle.getAttribute('data-target');
        const input = document.getElementById(targetId);

        if (!input) return;

        let open = false;
        toggle.setAttribute("role", "button");
        toggle.tabIndex = 0;
        renderEyeIcon(toggle, open);

        const handleToggle = () => {
            open = !open;
            input.type = open ? "text" : "password";
            renderEyeIcon(toggle, open);
        };

        toggle.addEventListener("click", handleToggle);
        toggle.addEventListener("keydown", (event) => {
            if (event.key === "Enter" || event.key === " ") {
                event.preventDefault();
                handleToggle();
            }
        });
    });
}

/* ============================
   ОСНОВНОЙ ФУНКЦИОНАЛ
============================ */

// DOM элементы
let emailInput, passInput;
let loginSubmit, googleBtn;
let nextParam = null;
let resendTimer = null; // Таймер для обратного отсчёта
let resendCountdown = 0; // Оставшееся время в секундах

function setFormDisabled(disabled) {
    [emailInput, passInput, loginSubmit, googleBtn].forEach(el => {
        if (!el) return;
        el.disabled = disabled;
    });
}

function clearFormErrors() {
    clearError("email-error");
    clearError("pass-error");
    clearError("form-error");
    // Восстанавливаем лейбл Email, если он был изменён
    resetEmailLabel();
}

/* ============================
   ОБРАБОТКА ЛОГИНА
============================ */
function initLoginHandler() {
    const loginForm = document.getElementById("login-form");
    
    if (!loginForm || !emailInput || !passInput) {
        console.warn("Login form elements not found");
        return;
    }

    loginForm.onsubmit = async (e) => {
        e.preventDefault(); // Предотвращаем отправку формы
        
        try {
            clearFormErrors();
            let errors = false;

            // Проверка email
            if (!emailInput.value.trim()) {
                setError("email-error", "Email is required");
                errors = true;
            } else if (!isValidEmail(emailInput.value)) {
                setError("email-error", "Invalid email");
                errors = true;
            }

            // Проверка пароля
            if (!passInput.value.trim()) {
                setError("pass-error", "Password is required");
                errors = true;
            }

            if (errors) return;

            // Блокируем форму
            setFormDisabled(true);

            // Получаем значения и проверяем что они не пустые
            const email = emailInput.value.trim();
            const password = passInput.value;

            if (!email || !password) {
                setFormDisabled(false);
                setError("form-error", "Email and password are required");
                return;
            }

            // Отправляем запрос
            const response = await fetch('/app/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    email: email,
                    password: password
                })
            });

            const data = await response.json();

            // Разблокируем форму
            setFormDisabled(false);

            // Обработка ответа
            if (data.success === true) {
                // Успешный вход - редирект
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    // Fallback: вызываем PortalEntry для входа в форум
                    try {
                        const portalEntryResponse = await fetch('/app/api/portal_entry.php', {
                            method: 'GET',
                            credentials: 'same-origin'
                        });
                        
                        if (!portalEntryResponse.ok) {
                            throw new Error('Failed to access forum');
                        }
                        
                        const portalEntryData = await portalEntryResponse.json();
                        
                        if (portalEntryData.success === true && portalEntryData.redirect_url) {
                            // Редирект на форум
                            window.location.href = portalEntryData.redirect_url;
                        } else {
                            throw new Error('No redirect URL in response');
                        }
                    } catch (portalError) {
                        console.error("Error accessing forum:", portalError);
                        setError("form-error", "Failed to access forum");
                        setFormDisabled(false);
                    }
                }
            } else {
                // Обработка ошибок
                if (data.error) {
                    switch (data.error) {
                        case "EMAIL_NOT_CONFIRMED":
                            // Показываем сообщение в лейбле Email с кликабельной ссылкой
                            showEmailNotConfirmedError(emailInput.value.trim());
                            break;
                        default:
                            // Показываем текст ошибки
                            setError("form-error", data.error || "Login failed");
                    }
                } else {
                    setError("form-error", "Login failed");
                }
            }
        } catch (error) {
            console.error("Error in login:", error);
            setFormDisabled(false);
            setError("form-error", "Network error");
        }
    };
}

/* ============================
   EMAIL NOT CONFIRMED
============================ */
function showEmailNotConfirmedError(email) {
    // Очищаем все error-блоки - НЕ используем их для этого кейса
    clearError("email-error");
    clearError("pass-error");
    clearError("form-error");
    
    // Находим лейбл Email по ID
    const emailLabel = document.getElementById("email-label");
    if (!emailLabel) return;
    
    // Обновляем лейбл: "Email · not confirmed · send confirmation email"
    const labelHtml = `Email · not confirmed · <a href="#" id="resend-link" data-email="${email}">send confirmation email</a>`;
    emailLabel.innerHTML = labelHtml;
    
    // Устанавливаем обработчик клика на ссылку
    setTimeout(() => {
        const resendLink = document.getElementById("resend-link");
        if (resendLink) {
            resendLink.onclick = (e) => {
                e.preventDefault();
                handleResendVerification(email);
            };
        }
    }, 0);
}

function handleResendVerification(email) {
    // Проверяем таймер - если активен, не отправляем
    if (resendCountdown > 0) {
        return;
    }
    
    const resendLink = document.getElementById("resend-link");
    if (!resendLink) return;
    
    // Отключаем ссылку
    resendLink.classList.add("disabled");
    resendLink.style.pointerEvents = "none";
    
    // Отправляем запрос
    fetch('/app/resend_verification.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email: email })
    })
    .then(response => response.json())
    .then(data => {
        // В любом случае запускаем таймер (даже если сервер вернул ошибку)
        startResendTimer();
    })
    .catch(error => {
        console.error("Error resending verification:", error);
        // В любом случае запускаем таймер
        startResendTimer();
    });
}

function startResendTimer() {
    // Останавливаем предыдущий таймер, если есть
    if (resendTimer) {
        clearInterval(resendTimer);
    }
    
    resendCountdown = 60;
    const resendLink = document.getElementById("resend-link");
    
    // Обновляем ссылку сразу
    if (resendLink) {
        resendLink.textContent = `send again in ${resendCountdown}s`;
    }
    
    // Запускаем таймер
    resendTimer = setInterval(() => {
        resendCountdown--;
        
        if (resendCountdown <= 0) {
            // Таймер закончился - восстанавливаем ссылку
            clearInterval(resendTimer);
            resendTimer = null;
            resendCountdown = 0;
            
            if (resendLink) {
                resendLink.classList.remove("disabled");
                resendLink.style.pointerEvents = "";
                resendLink.textContent = "send confirmation email";
            }
        } else {
            // Обновляем текст
            if (resendLink) {
                resendLink.textContent = `send again in ${resendCountdown}s`;
            }
        }
    }, 1000);
}

function resetEmailLabel() {
    // Восстанавливаем обычный лейбл Email
    const emailLabel = document.getElementById("email-label");
    if (emailLabel) {
        emailLabel.textContent = "Email";
    }
}

/* ============================
   GOOGLE
============================ */
function initGoogleHandler() {
    if (!googleBtn) {
        console.warn("Google button not found");
        return;
    }

    googleBtn.onclick = () => {
        try {
            // Формируем URL для Google OAuth
            let googleUrl = '/app/oauth/google/google_start.php';
            
            // Если есть параметр next, передаём его дальше
            if (nextParam) {
                googleUrl += '?next=' + encodeURIComponent(nextParam);
            }
            
            // Перенаправляем на Google OAuth
            window.location.href = googleUrl;
        } catch (error) {
            console.error("Error in Google OAuth:", error);
        }
    };
}

/* ============================
   ОБРАБОТКА GET-ПАРАМЕТРОВ
============================ */
function initUrlParams() {
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');
    const message = urlParams.get('message');
    
    if (error === 'email_not_verified') {
        // Показываем сообщение о необходимости подтверждения email
        const errorMsg = message || 'Please confirm your email';
        setError('form-error', errorMsg);
    } else if (message) {
        // Показываем общее сообщение, если есть
        setError('form-error', message);
    }
}

/* ============================
   ИНИЦИАЛИЗАЦИЯ
============================ */
function init() {
    try {
        // Получаем DOM элементы
        emailInput = document.getElementById("email");
        passInput = document.getElementById("password");
        loginSubmit = document.getElementById("login-submit");
        googleBtn = document.getElementById("google-btn");

        // Получаем параметр next из URL
        nextParam = getNextParam();

        // Обрабатываем GET-параметры (error, message)
        initUrlParams();

        // Инициализируем компоненты
        initPasswordToggles();
        initLoginHandler();
        initGoogleHandler();
        
        // Устанавливаем фокус на поле email при загрузке
        if (emailInput) {
            setTimeout(() => emailInput.focus(), 100);
        }
    } catch (error) {
        console.error("Initialization error:", error);
    }
}

// Ждем загрузки DOM
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    // DOM уже загружен
    init();
}
