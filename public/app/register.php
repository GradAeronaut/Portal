<?php
error_log('VERIFY_MAIL: register.php reached');
header('Content-Type: application/json');

// Разрешаем только POST-запросы
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'method_not_allowed']);
    exit;
}

// Получаем данные из JSON
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_json']);
    exit;
}

$username     = isset($input['username']) ? trim($input['username']) : '';
$displayName  = isset($input['display_name']) ? trim($input['display_name']) : '';
$email        = isset($input['email']) ? trim($input['email']) : '';
$password     = isset($input['password']) ? (string) $input['password'] : '';

// Базовая валидация
$errors = [];

if ($username === '') {
    $errors['username'] = 'Username is required';
}

if ($displayName === '') {
    $errors['display_name'] = 'Display name is required';
}

if ($email === '') {
    $errors['email'] = 'Email is required';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Invalid email format';
}

if ($password === '') {
    $errors['password'] = 'Password is required';
} elseif (strlen($password) < 8) {
    $errors['password'] = 'Password must be at least 8 characters';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['error' => 'validation_failed', 'details' => $errors]);
    exit;
}

// Подключение к базе данных через общий конфиг
$config  = require __DIR__ . '/../config/db.php';
$charset = $config['charset'] ?? 'utf8mb4';

if (!empty($config['socket'])) {
    $dsn = sprintf(
        'mysql:unix_socket=%s;dbname=%s;charset=%s',
        $config['socket'],
        $config['dbname'],
        $charset
    );
} else {
    $host = $config['host'] ?? '127.0.0.1';
    $port = (int) ($config['port'] ?? 3306);
    $dsn  = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $host,
        $port,
        $config['dbname'],
        $charset
    );
}

try {
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'database_error']);
    exit;
}

try {
    // Подключаем единый генератор публичного ID
    require_once __DIR__ . '/../php/PublicId.php';

    // Проверка уникальности username ДО создания пользователя и любых SSO действий
    $checkUsernameStmt = $pdo->prepare("
        SELECT id
        FROM users 
        WHERE username = :username
        LIMIT 1
    ");
    $checkUsernameStmt->execute([
        ':username' => $username,
    ]);
    $existingUsername = $checkUsernameStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingUsername) {
        // Username уже занят - возвращаем ошибку валидации
        http_response_code(400);
        echo json_encode([
            'error' => 'validation_failed',
            'details' => [
                'username' => 'This name is already taken. Please choose a different one.'
            ]
        ]);
        exit;
    }

    // Проверка уникальности email
    $checkEmailStmt = $pdo->prepare("
        SELECT id
        FROM users 
        WHERE email = :email
        LIMIT 1
    ");
    $checkEmailStmt->execute([
        ':email' => $email,
    ]);
    $existingEmail = $checkEmailStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingEmail) {
        // Email уже существует - возвращаем специальный код для редиректа на логин
        http_response_code(409);
        echo json_encode([
            'error' => 'ACCOUNT_EXISTS'
        ]);
        exit;
    }

    // Генерация уникального public_id (6 символов Crockford Base32)
    try {
        $publicId = PublicId::generate($pdo);
    } catch (RuntimeException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'public_id_generation_failed']);
        exit;
    }

    // Хеширование пароля
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Генерация verification_token (64 символа hex)
    $verificationToken = bin2hex(random_bytes(32));

    // Создание пользователя
    $insert = $pdo->prepare("
        INSERT INTO users (public_id, username, display_name, email, password_hash, role, email_verified, status, verification_token, created_at)
        VALUES (:public_id, :username, :display_name, :email, :password_hash, :role, :email_verified, :status, :verification_token, NOW())
    ");

    $insert->execute([
        ':public_id'         => $publicId,
        ':username'          => $username,
        ':display_name'     => $displayName,
        ':email'             => $email,
        ':password_hash'     => $passwordHash,
        ':role'              => 'standard',
        ':email_verified'    => 0,
        ':status'            => 'pending',
        ':verification_token' => $verificationToken,
    ]);

    $userId = (int) $pdo->lastInsertId();

    // Подключаем функции для отправки email
    require_once __DIR__ . '/../php/email_helpers.php';

    // Отправляем письмо подтверждения
    error_log('VERIFY_MAIL: before send_verification_email');
    if (!send_verification_email($email, $verificationToken)) {
        // Логируем ошибку, но не прерываем регистрацию
        // Пользователь может запросить повторную отправку позже
        error_log("Failed to send verification email to: {$email}");
    }

    // Создаём сессию для только что зарегистрированного пользователя
    require_once __DIR__ . '/../php/auth_helpers.php';
    if (!portal_login_success($pdo, $userId)) {
        http_response_code(500);
        echo json_encode(['error' => 'session_creation_failed']);
        exit;
    }

    // Возвращаем успех
    echo json_encode([
        'success' => true
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'internal_error']);
}


