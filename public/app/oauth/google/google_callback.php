<?php
/**
 * Google OAuth2 - Callback
 * Обработка ответа от Google и создание/вход пользователя
 */

session_start();

// Загружаем конфигурацию Google OAuth2
$google_config = require __DIR__ . '/../../../config/google_oauth.php';

$google_client_id = $google_config['client_id'];
$google_client_secret = $google_config['client_secret'];
$redirect_uri = $google_config['redirect_uri'];

// Редирект будет через SSO forward

// Проверка на ошибки от Google
if (isset($_GET['error'])) {
    // Google вернул ошибку (пользователь отказал в доступе и т.д.)
    header('Location: /auth/login/?error=oauth_cancelled');
    exit;
}

// Проверка наличия code
if (!isset($_GET['code'])) {
    header('Location: /auth/login/?error=oauth_invalid');
    exit;
}

// Проверка state для защиты от CSRF
if (!isset($_GET['state']) || !isset($_SESSION['oauth_state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
    header('Location: /auth/login/?error=oauth_invalid_state');
    exit;
}

// Очищаем state из сессии
unset($_SESSION['oauth_state']);

$code = $_GET['code'];

// ============================================================
// ШАГ 1: Обмениваем code на access_token
// ============================================================

$token_url = 'https://oauth2.googleapis.com/token';
$token_params = [
    'code' => $code,
    'client_id' => $google_client_id,
    'client_secret' => $google_client_secret,
    'redirect_uri' => $redirect_uri,
    'grant_type' => 'authorization_code'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $token_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_params));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$token_response = curl_exec($ch);
$token_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($token_http_code !== 200) {
    header('Location: /auth/login/?error=oauth_token_failed');
    exit;
}

$token_data = json_decode($token_response, true);

if (!isset($token_data['access_token'])) {
    header('Location: /auth/login/?error=oauth_token_invalid');
    exit;
}

$access_token = $token_data['access_token'];

// ============================================================
// ШАГ 2: Получаем информацию о пользователе
// ============================================================

$userinfo_url = 'https://www.googleapis.com/oauth2/v2/userinfo';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $userinfo_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$userinfo_response = curl_exec($ch);
$userinfo_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($userinfo_http_code !== 200) {
    header('Location: /auth/login/?error=oauth_userinfo_failed');
    exit;
}

$userinfo = json_decode($userinfo_response, true);

if (!isset($userinfo['id']) || !isset($userinfo['email'])) {
    header('Location: /auth/login/?error=oauth_userinfo_invalid');
    exit;
}

$google_id = $userinfo['id'];
$email = $userinfo['email'];
$name = isset($userinfo['name']) ? $userinfo['name'] : null;
$email_verified = isset($userinfo['verified_email']) ? (bool)$userinfo['verified_email'] : false;

// ============================================================
// ШАГ 3: Подключение к базе данных
// ============================================================

$config = require __DIR__ . '/../../../config/db.php';
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
    header('Location: /auth/login/?error=database_error');
    exit;
}

// ============================================================
// ШАГ 4: Найти или создать пользователя
// ============================================================

// Подключаем единый генератор публичного ID
require_once __DIR__ . '/../../../php/PublicId.php';

// Сначала ищем по google_id
$stmt = $pdo->prepare("SELECT id, email, email_verified, status FROM users WHERE google_id = ? LIMIT 1");
$stmt->execute([$google_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Если не нашли по google_id, ищем по email
if (!$user) {
    $stmt = $pdo->prepare("SELECT id, email, email_verified, status, google_id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Если нашли пользователя с таким email, привязываем google_id
    if ($user) {
        // Если у пользователя уже есть другой google_id - это конфликт
        if (!empty($user['google_id']) && $user['google_id'] !== $google_id) {
            header('Location: /auth/login/?error=email_conflict');
            exit;
        }
        
        // Привязываем google_id к существующему аккаунту
        $updateStmt = $pdo->prepare("UPDATE users SET google_id = ?, email_verified = 1, status = 'active' WHERE id = ?");
        $updateStmt->execute([$google_id, $user['id']]);
        
        // Обновляем данные пользователя
        $user['google_id'] = $google_id;
        $user['email_verified'] = 1;
        $user['status'] = 'active';
    }
}

// Если пользователя всё ещё нет - создаём нового
if (!$user) {
    // Генерируем уникальные username и display_name
    $base_username = 'user_' . substr($google_id, 0, 8);
    $username = $base_username;
    $counter = 1;
    
    // Проверяем уникальность username
    while (true) {
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $checkStmt->execute([$username]);
        if (!$checkStmt->fetch()) {
            break;
        }
        $username = $base_username . '_' . $counter++;
    }
    
    // Генерируем display_name из имени или email
    if ($name) {
        $display_name = $name;
    } else {
        $display_name = explode('@', $email)[0];
    }
    
    // Проверяем уникальность display_name
    $base_display_name = $display_name;
    $counter = 1;
    while (true) {
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE display_name = ? LIMIT 1");
        $checkStmt->execute([$display_name]);
        if (!$checkStmt->fetch()) {
            break;
        }
        $display_name = $base_display_name . ' ' . $counter++;
    }
    
    // Генерируем public_id (6 символов Crockford Base32)
    try {
        $public_id = PublicId::generate($pdo);
    } catch (RuntimeException $e) {
        header('Location: /auth/login/?error=public_id_failed');
        exit;
    }

    // Создаём пользователя
    $insertStmt = $pdo->prepare("
        INSERT INTO users (public_id, google_id, username, display_name, email, email_verified, status, role) 
        VALUES (?, ?, ?, ?, ?, 1, 'active', 'standard')
    ");
    $insertStmt->execute([$public_id, $google_id, $username, $display_name, $email]);
    
    $user_id = $pdo->lastInsertId();
    
    // Загружаем созданного пользователя
    $stmt = $pdo->prepare("SELECT id, email, email_verified, status FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// ============================================================
// ШАГ 5: Создаём сессию
// ============================================================

$sessionToken = bin2hex(random_bytes(32)); // 64-символьный hex-токен
$expiresAt = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60)); // 30 дней

// Записываем сессию в таблицу sessions
$sessionStmt = $pdo->prepare("INSERT INTO sessions (user_id, token, expires_at) VALUES (?, ?, ?)");
$sessionStmt->execute([$user['id'], $sessionToken, $expiresAt]);

// Устанавливаем cookie с токеном сессии
$cookieOptions = [
    'expires' => time() + (30 * 24 * 60 * 60), // 30 дней
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Lax'
];
setcookie('session_token', $sessionToken, $cookieOptions);

// PHP Session (опционально)
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_email'] = $user['email'];

// Очищаем oauth_next из сессии
unset($_SESSION['oauth_next']);

// ============================================================
// ШАГ 6: Создаём SSO токен и редиректим через форум
// ============================================================

// Редирект на главную страницу портала
header('Location: /');
exit;
?>

