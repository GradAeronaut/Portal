<?php
/**
 * Quick script to check current users in database
 */

require_once __DIR__ . '/../config/db.php';

$config = require __DIR__ . '/../config/db.local.php';
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
    
    echo "=== Checking Portal Database ===\n\n";
    
    // Check users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Users in database: $userCount\n";
    
    if ($userCount > 0) {
        echo "\n=== User List ===\n";
        $stmt = $pdo->query("SELECT id, username, email, display_name, public_id, status FROM users ORDER BY id");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($users as $user) {
            echo sprintf(
                "ID: %d | Username: %s | Email: %s | Display: %s | Public ID: %s | Status: %s\n",
                $user['id'],
                $user['username'] ?: 'NULL',
                $user['email'] ?: 'NULL',
                $user['display_name'] ?: 'NULL',
                $user['public_id'],
                $user['status']
            );
        }
    }
    
    // Check related tables
    echo "\n=== Related Data ===\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM portal_forum_link");
    $forumLinks = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Portal-Forum links: $forumLinks\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM sso_tokens");
    $ssoTokens = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "SSO tokens: $ssoTokens\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM sessions");
    $sessions = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Sessions: $sessions\n";
    
    echo "\n=== Status ===\n";
    if ($userCount == 0) {
        echo "✅ Database is CLEAN - no users found\n";
        echo "   You can now test registration with any email\n";
    } else {
        echo "⚠️  Database still contains $userCount user(s)\n";
        echo "   Run cleanup script to remove them:\n";
        echo "   mysql -u sinbad_user -p --socket=/tmp/mysql.sock sinbad_db < tools/sql/cleanup-test-users.sql\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}


