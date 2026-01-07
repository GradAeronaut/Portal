<?php
// Файл verify.php - автоматическая верификация email, создание сессии и редирект

// Получаем токен из GET-параметра
if (!isset($_GET['token']) || empty($_GET['token'])) {
    http_response_code(400);
    die("Verification link is invalid or expired.");
}

$token = trim($_GET['token']);

// Подключение к базе данных
$config = require __DIR__ . '/../config/db.php';
$charset  = $config['charset'] ?? 'utf8mb4';

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
    $dsn = sprintf(
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
    die("Database connection failed. Please try again later.");
}

// Ищем пользователя по verification_token
$stmt = $pdo->prepare("SELECT id, email FROM users WHERE verification_token = ? LIMIT 1");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Если пользователь не найден
if (!$user) {
    http_response_code(400);
    die("Verification link is invalid or expired.");
}

// Обновляем данные пользователя
$updateStmt = $pdo->prepare("
    UPDATE users 
    SET email_verified = 1, 
        status = 'active', 
        verification_token = NULL 
    WHERE id = ?
");
$updateStmt->execute([$user['id']]);

// Создаем новую сессию
// Генерируем 64-символьный hex-токен
$sessionToken = bin2hex(random_bytes(32));

// Срок действия сессии - 30 дней
$expiresAt = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60));

// Записываем сессию в таблицу sessions
$sessionStmt = $pdo->prepare("
    INSERT INTO sessions (user_id, token, expires_at) 
    VALUES (?, ?, ?)
");
$sessionStmt->execute([$user['id'], $sessionToken, $expiresAt]);

// Устанавливаем cookie с токеном сессии
$cookieOptions = [
    'expires' => time() + (30 * 24 * 60 * 60), // 30 дней
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', // Secure только для HTTPS
    'httponly' => true,
    'samesite' => 'Lax'
];

setcookie('session_token', $sessionToken, $cookieOptions);

// Редирект на Shape после успешной верификации
header("Location: /shape-sinbad/");
exit;
?>

