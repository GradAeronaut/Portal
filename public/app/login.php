<?php
header('Content-Type: application/json');

// Начинаем сессию
session_start();

// Разрешаем только POST запросы
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed'
    ]);
    exit;
}

// Получаем данные из JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['email']) || !isset($input['password'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Email and password are required'
    ]);
    exit;
}

$email = trim($input['email']);
$password = $input['password'];

// Валидация email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid credentials'
    ]);
    exit;
}

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
    echo json_encode([
        'success' => false,
        'error' => 'Invalid credentials'
    ]);
    exit;
}

// Поиск пользователя по email
$stmt = $pdo->prepare("SELECT id, email, password_hash, email_verified, status FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Проверка существования пользователя
if (!$user) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid credentials'
    ]);
    exit;
}

// Проверка подтверждения email
if (!$user['email_verified']) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'EMAIL_NOT_CONFIRMED'
    ]);
    exit;
}

// Проверка пароля
if (!password_verify($password, $user['password_hash'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid credentials'
    ]);
    exit;
}

// Подключаем общую функцию успешного входа
require_once __DIR__ . '/../php/auth_helpers.php';

// Успешный вход - используем универсальную функцию
if (!portal_login_success($pdo, (int) $user['id'])) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid credentials'
    ]);
    exit;
}

// Успешный ответ
echo json_encode([
    'success' => true,
    'redirect' => '/forum/'
]);
exit;
