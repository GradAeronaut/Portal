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

/* Показ ошибки */
function setError(id, msg) {
    const el = document.getElementById(id);
    if (el) el.textContent = msg || "";
}

/* Очистка ошибки */
function clearError(id) {
    const el = document.getElementById(id);
    if (el) el.textContent = "";
}

/* Получение токена из URL */
function getTokenFromURL() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('token') || null;
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
let newPassInput, repeatPassInput;
let submitBtn;
let token = null;

function setFormDisabled(disabled) {
    [newPassInput, repeatPassInput, submitBtn].forEach(el => {
        if (!el) return;
        el.disabled = disabled;
    });
}

function clearFormErrors() {
    clearError("pass-error");
    clearError("repeat-error");
    clearError("form-error");
}

/* ============================
   ОБРАБОТКА RESET
============================ */
function initResetHandler() {
    const resetForm = document.getElementById("reset-form");
    
    if (!resetForm || !newPassInput || !repeatPassInput) {
        console.warn("Reset form elements not found");
        return;
    }

    resetForm.onsubmit = async (e) => {
        e.preventDefault(); // Предотвращаем отправку формы
        
        try {
            clearFormErrors();
            let errors = false;

            // Проверка токена
            if (!token) {
                setError("form-error", "Reset link is invalid or expired.");
                return;
            }

            // Проверка нового пароля
            const newPass = newPassInput.value.trim();
            if (!newPass) {
                setError("pass-error", "Password is required");
                errors = true;
            } else if (newPass.length < 6) {
                setError("pass-error", "Password must be at least 6 characters");
                errors = true;
            }

            // Проверка повтора пароля
            const repeatPass = repeatPassInput.value.trim();
            if (!repeatPass) {
                setError("repeat-error", "Please repeat your password");
                errors = true;
            } else if (newPass !== repeatPass) {
                setError("repeat-error", "Passwords do not match");
                errors = true;
            }

            if (errors) return;

            // Блокируем форму
            setFormDisabled(true);

            // Отправляем запрос
            const response = await fetch('/app/reset.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    token: token,
                    password: newPass
                })
            });

            const data = await response.json();

            // Разблокируем форму
            setFormDisabled(false);

            // Обработка ответа
            if (data.ok === true) {
                // Успешный сброс пароля
                alert("Password reset successfully! You can now sign in with your new password.");
                window.location = "/auth/login/";
            } else if (data.error) {
                // Обработка ошибок
                switch (data.error) {
                    case "invalid_token":
                        setError("form-error", "Reset link is invalid or expired.");
                        break;
                    case "token_expired":
                        setError("form-error", "Reset link is invalid or expired.");
                        break;
                    case "weak_password":
                        setError("pass-error", "Password is too weak");
                        break;
                    default:
                        setError("form-error", data.message || "An error occurred");
                }
            } else {
                setError("form-error", "An error occurred");
            }
        } catch (error) {
            console.error("Error in reset:", error);
            setFormDisabled(false);
            setError("form-error", "An error occurred during password reset");
        }
    };
}

/* ============================
   ИНИЦИАЛИЗАЦИЯ
============================ */
function init() {
    try {
        // Получаем токен из URL
        token = getTokenFromURL();

        // Получаем DOM элементы
        newPassInput = document.getElementById("new-pass");
        repeatPassInput = document.getElementById("new-pass-repeat");
        submitBtn = document.getElementById("submit-btn");

        // Инициализируем компоненты
        initPasswordToggles();
        initResetHandler();
        
        // Устанавливаем фокус на поле нового пароля при загрузке
        if (newPassInput) {
            setTimeout(() => newPassInput.focus(), 100);
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

