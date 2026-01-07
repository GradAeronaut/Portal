/* ============================
   УТИЛИТЫ
============================ */

/* Проверка email */
function isValidEmail(email) {
    return /^[^@]+@[^@]+\.[^@]+$/.test(email.trim());
}

/* Показ ошибки */
function setError(msg) {
    const el = document.getElementById("error");
    if (el) el.textContent = msg || "";
}

/* Очистка ошибки */
function clearError() {
    const el = document.getElementById("error");
    if (el) el.textContent = "";
}

/* ============================
   ОСНОВНОЙ ФУНКЦИОНАЛ
============================ */

// DOM элементы
let emailInput, submitBtn;

function setFormDisabled(disabled) {
    if (emailInput) emailInput.disabled = disabled;
    if (submitBtn) submitBtn.disabled = disabled;
}

/* ============================
   ОБРАБОТКА ФОРМЫ
============================ */
function initRecoverHandler() {
    const recoverForm = document.getElementById("recover-form");
    
    if (!recoverForm || !emailInput) {
        console.warn("Recover form elements not found");
        return;
    }

    recoverForm.onsubmit = (e) => {
        e.preventDefault(); // Предотвращаем отправку формы
        
        try {
            clearError();
            let errors = false;

            // Проверка на пустое поле
            if (!emailInput.value.trim()) {
                setError("Email is required");
                errors = true;
            } else if (!isValidEmail(emailInput.value)) {
                // Проверка email по RegExp
                setError("Invalid email");
                errors = true;
            }

            if (errors) return;

            // Успех: alert stub (здесь будет реальный запрос)
            alert("Reset link sent");
        } catch (error) {
            console.error("Error in recover:", error);
            setError("An error occurred");
        }
    };
}

/* ============================
   ИНИЦИАЛИЗАЦИЯ
============================ */
function init() {
    try {
        // Получаем DOM элементы
        emailInput = document.getElementById("email");
        submitBtn = document.getElementById("submit-btn");

        // Инициализируем обработчик
        initRecoverHandler();
        
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
