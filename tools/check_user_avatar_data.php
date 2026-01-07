<?php
/**
 * Скрипт для проверки данных пользователя для аватара
 * Использование: php tools/check_user_avatar_data.php [user_id]
 */

session_start();

require_once __DIR__ . '/../config/db.php';

$userId = $_SERVER['argv'][1] ?? $_SESSION['user_id'] ?? null;

if (!$userId) {
    echo "Error: No user ID provided. Usage: php check_user_avatar_data.php [user_id]\n";
    echo "Or make sure you're logged in (session has user_id).\n";
    exit(1);
}

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT id, display_name, username, public_id, email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row) {
        echo "Error: User with ID $userId not found.\n";
        exit(1);
    }
    
    echo "=== User Data for ID: $userId ===\n";
    echo "display_name: " . ($row['display_name'] ?: '(NULL or empty)') . "\n";
    echo "username: " . ($row['username'] ?: '(NULL or empty)') . "\n";
    echo "public_id: " . $row['public_id'] . "\n";
    echo "email: " . $row['email'] . "\n\n";
    
    // Determine what will be used for avatar
    $nameForAvatar = '';
    if (!empty($row['display_name']) && trim($row['display_name']) !== '') {
        $nameForAvatar = trim($row['display_name']);
        echo "✓ Will use display_name: '$nameForAvatar'\n";
    } elseif (!empty($row['username']) && trim($row['username']) !== '') {
        $nameForAvatar = trim($row['username']);
        echo "✓ Will use username: '$nameForAvatar'\n";
    } elseif (!empty($row['public_id'])) {
        $nameForAvatar = $row['public_id'];
        echo "⚠ Will use public_id (not ideal): '$nameForAvatar'\n";
    } else {
        echo "✗ No valid name found - will show 'U'\n";
        exit(0);
    }
    
    // Calculate first letter
    $firstLetter = mb_substr($nameForAvatar, 0, 1, 'UTF-8');
    $firstLetter = mb_strtoupper($firstLetter, 'UTF-8');
    echo "\nFirst letter for avatar: '$firstLetter'\n";
    
    if ($firstLetter === 'U' && strpos($nameForAvatar, $row['public_id']) === 0) {
        echo "\n⚠ WARNING: Avatar shows 'U' because using public_id which starts with 'U'.\n";
        echo "Solution: Update display_name or username in database:\n";
        echo "  UPDATE users SET display_name = 'YourName' WHERE id = $userId;\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

