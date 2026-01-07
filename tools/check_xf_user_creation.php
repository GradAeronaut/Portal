<?php
/**
 * Диагностический скрипт для проверки создания пользователя в XF
 * Проверяет наличие пользователя sever212@gmail.com в обеих БД
 */

require_once __DIR__ . '/../config/db.php';

$portalConfig = require __DIR__ . '/../config/db.php';
$testEmail = 'sever212@gmail.com';

echo "=== ПРОВЕРКА СОЗДАНИЯ XF-ПОЛЬЗОВАТЕЛЯ ===\n\n";

// 1. Подключение к БД Portal
echo "1. Подключение к БД Portal...\n";
try {
    $portalDsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $portalConfig['host'] ?? '127.0.0.1',
        $portalConfig['port'] ?? 3306,
        $portalConfig['dbname'],
        $portalConfig['charset'] ?? 'utf8mb4'
    );
    $portalPdo = new PDO($portalDsn, $portalConfig['username'], $portalConfig['password']);
    $portalPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "   ✓ Подключено к БД: {$portalConfig['dbname']}\n\n";
} catch (PDOException $e) {
    die("   ✗ Ошибка подключения к Portal: " . $e->getMessage() . "\n");
}

// 2. Поиск пользователя в Portal по email
echo "2. Поиск пользователя в Portal по email: {$testEmail}\n";
$stmt = $portalPdo->prepare("SELECT id, public_id, username, display_name, email, status FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$testEmail]);
$portalUser = $stmt->fetch(PDO::FETCH_ASSOC);

if ($portalUser) {
    echo "   ✓ Пользователь найден в Portal:\n";
    echo "      ID: {$portalUser['id']}\n";
    echo "      Public ID: {$portalUser['public_id']}\n";
    echo "      Username: {$portalUser['username']}\n";
    echo "      Display Name: {$portalUser['display_name']}\n";
    echo "      Status: {$portalUser['status']}\n\n";
    
    $publicId = $portalUser['public_id'];
    $displayName = $portalUser['display_name'];
} else {
    echo "   ✗ Пользователь НЕ найден в Portal\n\n";
    exit(1);
}

// 3. Проверка portal_forum_link
echo "3. Проверка portal_forum_link для public_id: {$publicId}\n";
$stmt = $portalPdo->prepare("SELECT forum_user_id FROM portal_forum_link WHERE public_id = ? LIMIT 1");
$stmt->execute([$publicId]);
$link = $stmt->fetch(PDO::FETCH_ASSOC);

if ($link && !empty($link['forum_user_id'])) {
    $forumUserId = (int) $link['forum_user_id'];
    echo "   ✓ Связь найдена: forum_user_id = {$forumUserId}\n\n";
} else {
    echo "   ✗ Связь НЕ найдена - пользователь не был создан в XF\n\n";
    exit(1);
}

// 4. Попытка найти конфигурацию XF
echo "4. Поиск конфигурации XF...\n";
$xfConfigPath = __DIR__ . '/../forum/src/config.php';
if (file_exists($xfConfigPath)) {
    $xfConfig = require $xfConfigPath;
    echo "   ✓ Конфигурация XF найдена\n";
    echo "      БД: {$xfConfig['db']['dbname']}\n";
    echo "      Host: {$xfConfig['db']['host']}\n\n";
    
    $xfDbName = $xfConfig['db']['dbname'];
    $xfHost = $xfConfig['db']['host'];
    $xfPort = $xfConfig['db']['port'] ?? 3306;
    $xfUser = $xfConfig['db']['username'];
    $xfPass = $xfConfig['db']['password'];
} else {
    echo "   ⚠ Конфигурация XF не найдена (forum/src/config.php)\n";
    echo "      Используем настройки Portal для подключения к XF\n\n";
    
    // Предполагаем, что XF использует ту же БД или другую на том же хосте
    $xfDbName = 'xf'; // Попробуем стандартное имя
    $xfHost = $portalConfig['host'] ?? '127.0.0.1';
    $xfPort = $portalConfig['port'] ?? 3306;
    $xfUser = $portalConfig['username'];
    $xfPass = $portalConfig['password'];
}

// 5. Подключение к БД XF и поиск пользователя
echo "5. Подключение к БД XF ({$xfDbName})...\n";
try {
    $xfDsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $xfHost,
        $xfPort,
        $xfDbName
    );
    $xfPdo = new PDO($xfDsn, $xfUser, $xfPass);
    $xfPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "   ✓ Подключено к БД XF: {$xfDbName}\n\n";
} catch (PDOException $e) {
    echo "   ✗ Ошибка подключения к XF: " . $e->getMessage() . "\n";
    echo "      Попробуйте проверить конфигурацию в forum/src/config.php\n\n";
    exit(1);
}

// 6. Поиск пользователя в XF по user_id
echo "6. Поиск пользователя в XF по user_id: {$forumUserId}\n";
$stmt = $xfPdo->prepare("SELECT user_id, username, email FROM xf_user WHERE user_id = ? LIMIT 1");
$stmt->execute([$forumUserId]);
$xfUser = $stmt->fetch(PDO::FETCH_ASSOC);

if ($xfUser) {
    echo "   ✓ Пользователь найден в XF:\n";
    echo "      User ID: {$xfUser['user_id']}\n";
    echo "      Username: {$xfUser['username']}\n";
    echo "      Email: {$xfUser['email']}\n\n";
} else {
    echo "   ✗ Пользователь НЕ найден в XF по user_id: {$forumUserId}\n\n";
    
    // 7. Поиск по email
    echo "7. Поиск пользователя в XF по email: {$testEmail}\n";
    $stmt = $xfPdo->prepare("SELECT user_id, username, email FROM xf_user WHERE email = ? LIMIT 1");
    $stmt->execute([$testEmail]);
    $xfUserByEmail = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($xfUserByEmail) {
        echo "   ⚠ Пользователь найден по email, но с другим user_id:\n";
        echo "      User ID: {$xfUserByEmail['user_id']} (ожидался {$forumUserId})\n";
        echo "      Username: {$xfUserByEmail['username']}\n\n";
        echo "   ПРОБЛЕМА: Несоответствие user_id в portal_forum_link и xf_user\n";
    } else {
        echo "   ✗ Пользователь НЕ найден по email\n\n";
    }
    
    // 8. Поиск по username (display_name)
    echo "8. Поиск пользователя в XF по username: {$displayName}\n";
    $stmt = $xfPdo->prepare("SELECT user_id, username, email FROM xf_user WHERE username = ? LIMIT 1");
    $stmt->execute([$displayName]);
    $xfUserByName = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($xfUserByName) {
        echo "   ⚠ Пользователь найден по username, но с другим user_id:\n";
        echo "      User ID: {$xfUserByName['user_id']} (ожидался {$forumUserId})\n";
        echo "      Email: {$xfUserByName['email']}\n\n";
        echo "   ПРОБЛЕМА: Несоответствие user_id в portal_forum_link и xf_user\n";
    } else {
        echo "   ✗ Пользователь НЕ найден по username\n\n";
    }
    
    echo "\n=== ВЫВОД ===\n";
    echo "Пользователь НЕ был создан в XF через /sso/create-user\n";
    echo "Или был создан в другой БД/инсталляции XF\n";
    exit(1);
}

echo "\n=== ВЫВОД ===\n";
echo "✓ Пользователь успешно найден в обеих БД\n";
echo "✓ Связь portal_forum_link корректна\n";
echo "✓ SSO должен работать корректно\n";


