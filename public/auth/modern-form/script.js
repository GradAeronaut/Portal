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
    if (el) {
        el.textContent = msg || "";
        el.style.display = msg ? "block" : "none";
    } else {
        console.error("Element not found:", id);
    }
}

/* Очистка ошибки */
function clearError(id) {
    const el = document.getElementById(id);
    if (el) {
        el.textContent = "";
        el.style.display = "none";
    }
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

// DOM элементы (будут инициализированы после загрузки DOM)
let nameInput, nameApply, nameCancel;
let emailInput, passInput, repInput;
let regSubmit, googleBtn, overlay;

function setRegistrationDisabled(disabled) {
    [emailInput, passInput, repInput, regSubmit, googleBtn].forEach(el => {
        if (!el) return;
        el.disabled = disabled;
    });
}

function resetRegistrationForm() {
    if (emailInput) {
        emailInput.value = "";
        clearError("email-error");
    }
    if (passInput) {
        passInput.value = "";
        clearError("pass-error");
    }
    if (repInput) {
        repInput.value = "";
        clearError("repeat-error");
    }
    setError("form-error", "");
}

// СТАРТ: всё, кроме Public Name, заблокировано
function lockRegistration({ clearForm = false } = {}) {
    if (overlay) {
        overlay.classList.add("active");
    }
    setRegistrationDisabled(true);
    if (nameInput) {
        nameInput.disabled = false;
    }
    if (clearForm) {
        resetRegistrationForm();
    }
}

function unlockRegistration() {
    if (overlay) {
        overlay.classList.remove("active");
    }
    setRegistrationDisabled(false);
    if (nameInput) {
        nameInput.disabled = true;
    }
}


/* ============================
   ПРОВЕРКА PUBLIC NAME
============================ */
function initNameHandlers() {
    if (!nameApply || !nameInput || !nameCancel) {
        console.warn("Name form elements not found");
        return;
    }

    nameApply.onclick = async () => {
        try {
            if (!nameInput) return;
            const name = nameInput.value.trim();

            if (!name) {
                setError("name-error", "Name cannot be empty");
                return;
            }

            // Проверка на зарезервированные имена
            const taken = ["admin", "root", "test"];

            if (taken.includes(name.toLowerCase())) {
                setError("name-error", "This name is already taken. Please choose a different one.");
                return;
            }

            // Проверка уникальности username через API
            try {
                nameApply.disabled = true; // Блокируем кнопку во время проверки
                clearError("name-error"); // Очищаем предыдущие ошибки
                
                const response = await fetch('/app/api/check_username.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        username: name
                    })
                });

                if (!response.ok) {
                    // HTTP ошибка
                    setError("name-error", "This name is already taken. Please choose a different one.");
                    nameApply.disabled = false;
                    return;
                }

                const data = await response.json();

                // Проверяем различные варианты ответа
                if (data.error) {
                    // Ошибка от сервера (database_error, invalid_json и т.д.)
                    setError("name-error", "This name is already taken. Please choose a different one.");
                    nameApply.disabled = false;
                    return;
                }

                if (data.available === false) {
                    // Username занят
                    const message = data.message || "This name is already taken. Please choose a different one.";
                    setError("name-error", message);
                    nameApply.disabled = false;
                    return;
                }

                if (data.available !== true) {
                    // Неожиданный формат ответа - считаем, что имя занято для безопасности
                    setError("name-error", "This name is already taken. Please choose a different one.");
                    nameApply.disabled = false;
                    return;
                }

                // Username свободен
                clearError("name-error");
                nameApply.disabled = false;

                // Имя принято – открываем регистрацию
                unlockRegistration();
            } catch (error) {
                console.error("Error checking username:", error);
                setError("name-error", "This name is already taken. Please choose a different one.");
                nameApply.disabled = false;
            }
        } catch (error) {
            console.error("Error in name validation:", error);
            setError("name-error", "An error occurred");
        }
    };

    nameCancel.onclick = () => {
        try {
            if (nameInput) nameInput.value = "";
            setError("name-error", "");
            lockRegistration({ clearForm: true });
            if (nameInput) {
                nameInput.value = "";
                // Возвращаем фокус на поле имени для мигающего курсора
                setTimeout(() => nameInput.focus(), 50);
            }
        } catch (error) {
            console.error("Error in name cancel:", error);
        }
    };
}


/* ============================
   ПРОВЕРКА РЕГИСТРАЦИИ
============================ */
function initRegistrationHandler() {
    const regForm = document.getElementById("registration-form");
    
    if (!regForm || !emailInput || !passInput || !repInput) {
        console.warn("Registration form elements not found");
        return;
    }

    regForm.onsubmit = (e) => {
        e.preventDefault(); // Предотвращаем отправку формы
        
        try {
            let errors = false;

            // Email
            if (!emailInput || !isValidEmail(emailInput.value)) {
                setError("email-error", "Invalid email");
                errors = true;
            } else {
                clearError("email-error");
            }

            // Пароль
            if (!passInput || passInput.value.length < 8) {
                setError("pass-error", "Password must be at least 8 characters");
                errors = true;
            } else {
                clearError("pass-error");
            }

            // Повтор пароля
            if (!repInput || !passInput || repInput.value !== passInput.value) {
                setError("repeat-error", "Passwords do not match");
                errors = true;
            } else {
                clearError("repeat-error");
            }

            if (errors) return;

            // Отправляем AJAX запрос на регистрацию
            setRegistrationDisabled(true);
            setError("form-error", "");

            fetch('/app/register.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    username: nameInput ? nameInput.value.trim() : '',
                    display_name: nameInput ? nameInput.value.trim() : '',
                    email: emailInput.value.trim(),
                    password: passInput.value
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success === true) {
                    // Редирект через SSO forward после успешной регистрации
                    if (data.redirect_url) {
                        window.location.href = data.redirect_url;
                    } else if (data.sso_token) {
                        window.location.href = `/forum/index.php?r=sso/forward&token=${data.sso_token}`;
                    } else {
                        // Fallback на старый редирект (на случай если сервер не вернул токен)
                        window.location.href = '/shape-sinbad/';
                    }
                } else if (data.error === 'ACCOUNT_EXISTS') {
                    // Аккаунт уже существует - редирект на логин
                    window.location.href = '/auth/login/';
                } else {
                    // Обработка ошибок
                    if (data.error === 'validation_failed' && data.details) {
                        if (data.details.username) {
                            setError("name-error", data.details.username);
                        }
                        if (data.details.display_name) {
                            setError("name-error", data.details.display_name);
                        }
                        if (data.details.email) {
                            setError("email-error", data.details.email);
                        }
                        if (data.details.password) {
                            setError("pass-error", data.details.password);
                        }
                    } else {
                        setError("form-error", "Registration failed. Please try again.");
                    }
                    setRegistrationDisabled(false);
                }
            })
            .catch(error => {
                console.error("Error in registration:", error);
                setError("form-error", "An error occurred during registration");
                setRegistrationDisabled(false);
            });
        } catch (error) {
            console.error("Error in registration:", error);
            setError("form-error", "An error occurred during registration");
            setRegistrationDisabled(false);
        }
    };
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
            // Перенаправляем на Google OAuth (для регистрации)
            window.location.href = '/app/oauth/google/google_start.php';
        } catch (error) {
            console.error("Error in Google OAuth:", error);
        }
    };
}


/* ============================
   ИНИЦИАЛИЗАЦИЯ
============================ */
function init() {
    try {
        // Получаем DOM элементы
        nameInput  = document.getElementById("public-name");
        nameApply  = document.getElementById("name-apply");
        nameCancel = document.getElementById("name-cancel");

        emailInput = document.getElementById("email");
        passInput  = document.getElementById("password");
        repInput   = document.getElementById("repeat");

        regSubmit  = document.getElementById("reg-submit");
        googleBtn  = document.getElementById("google-btn");
        overlay    = document.getElementById("reg-overlay");

        // Инициализируем компоненты
        initPasswordToggles();
        initNameHandlers();
        initRegistrationHandler();
        initGoogleHandler();

        // Блокируем регистрацию при старте
        lockRegistration({ clearForm: true });
        
        // Устанавливаем фокус на поле имени при загрузке
        if (nameInput) {
            setTimeout(() => nameInput.focus(), 100);
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
