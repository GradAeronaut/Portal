<?php
/**
 * Portal Authentication Helpers
 * Общие функции для авторизации пользователей
 */

/**
 * Универсальная функция успешного входа/регистрации
 * Создает сессию в БД, устанавливает cookie и PHP сессию
 * 
 * @param PDO $pdo Подключение к БД
 * @param int $userId ID пользователя
 * @return bool true в случае успеха, false при ошибке
 */
function portal_login_success(PDO $pdo, int $userId): bool {
    // Загружаем полные данные пользователя из БД
    $userStmt = $pdo->prepare("
        SELECT id, public_id, username, display_name, email, role, status, email_verified
        FROM users 
        WHERE id = ? 
        LIMIT 1
    ");
    $userStmt->execute([$userId]);
    $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData) {
        return false;
    }
    
    // Генерируем токен сессии (64 hex символа)
    $sessionToken = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60)); // 30 дней
    
    // Создаем сессию в БД
    try {
        $sessionStmt = $pdo->prepare("INSERT INTO sessions (user_id, token, expires_at) VALUES (?, ?, ?)");
        $sessionStmt->execute([$userId, $sessionToken, $expiresAt]);
    } catch (PDOException $e) {
        return false;
    }
    
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
    
    // Устанавливаем PHP сессию (если еще не запущена)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Заполняем PHP сессию всеми необходимыми данными
    $_SESSION['user_id'] = (int) $userData['id'];
    $_SESSION['public_id'] = $userData['public_id'] ?? '';
    $_SESSION['username'] = $userData['username'] ?? '';
    $_SESSION['display_name'] = $userData['display_name'] ?? '';
    $_SESSION['email'] = $userData['email'] ?? '';
    $_SESSION['role'] = $userData['role'] ?? 'standard';
    $_SESSION['status'] = $userData['status'] ?? 'pending';
    $_SESSION['email_verified'] = (bool) $userData['email_verified'];
    
    return true;
}



