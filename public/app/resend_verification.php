<?php
error_log('VERIFY_MAIL: resend_verification.php reached');
header('Content-Type: application/json');

// Разрешаем только POST-запросы
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'method_not_allowed']);
    exit;
}

// Получаем данные из JSON
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'invalid_json']);
    exit;
}

$email = isset($input['email']) ? trim($input['email']) : '';

// Базовая валидация
if ($email === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'email_required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'invalid_email']);
    exit;
}

// Rate-limiting: проверяем последний запрос с этого IP (1 запрос / 60 секунд)
$clientIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$rateLimitDir = __DIR__ . '/../tmp/rate_limit';
$rateLimitFile = $rateLimitDir . '/' . md5($clientIp) . '.txt';

// Создаём директорию, если её нет
if (!is_dir($rateLimitDir)) {
    @mkdir($rateLimitDir, 0755, true);
}

// Проверяем rate-limit
if (file_exists($rateLimitFile)) {
    $lastRequestTime = (int) file_get_contents($rateLimitFile);
    $timeSinceLastRequest = time() - $lastRequestTime;
    
    if ($timeSinceLastRequest < 60) {
        // Rate limit превышен - возвращаем успех (не раскрываем информацию)
        echo json_encode(['success' => true]);
        exit;
    }
}

// Сохраняем текущее время
@file_put_contents($rateLimitFile, (string) time());

// Подключение к базе данных
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
    echo json_encode(['success' => false, 'error' => 'database_error']);
    exit;
}

try {
    // Проверяем, существует ли пользователь с таким email и не подтвержден ли email
    $checkStmt = $pdo->prepare("
        SELECT id, email, email_verified, verification_token
        FROM users 
        WHERE email = :email
        LIMIT 1
    ");
    $checkStmt->execute([':email' => $email]);
    $user = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Пользователь не найден - возвращаем успех для безопасности (чтобы не раскрывать, какие email зарегистрированы)
        echo json_encode(['success' => true]);
        exit;
    }

    // Если email уже подтвержден, возвращаем успех без отправки
    if ($user['email_verified'] == 1) {
        echo json_encode(['success' => true]);
        exit;
    }

    // Генерируем новый verification_token (64 символа hex)
    $verificationToken = bin2hex(random_bytes(32));

    // Обновляем токен в базе данных
    $updateStmt = $pdo->prepare("
        UPDATE users 
        SET verification_token = :token 
        WHERE id = :id
    ");
    $updateStmt->execute([
        ':token' => $verificationToken,
        ':id'    => $user['id']
    ]);

    // Подключаем функции для отправки email
    require_once __DIR__ . '/../php/email_helpers.php';

    // Отправляем письмо подтверждения
    error_log('VERIFY_MAIL: before send_verification_email');
    if (!send_verification_email($email, $verificationToken)) {
        error_log("Failed to resend verification email to: {$email}");
        // Возвращаем успех в любом случае, чтобы не раскрывать проблемы с отправкой
    }

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    error_log("Error in resend_verification.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'internal_error']);
}

