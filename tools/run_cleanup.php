<?php
/**
 * Run cleanup script to remove all users
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
    
    echo "=== Starting Cleanup ===\n\n";
    
    // Check current state
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $beforeCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Users before cleanup: $beforeCount\n\n";
    
    // Start transaction
    $pdo->beginTransaction();
    
    echo "Deleting portal_forum_link entries...\n";
    $pdo->exec("DELETE FROM portal_forum_link");
    echo "  ✓ Deleted\n";
    
    echo "Deleting portal_kneeboard_link entries...\n";
    $pdo->exec("DELETE FROM portal_kneeboard_link");
    echo "  ✓ Deleted\n";
    
    echo "Deleting sso_tokens entries...\n";
    $pdo->exec("DELETE FROM sso_tokens");
    echo "  ✓ Deleted\n";
    
    echo "Deleting sessions entries...\n";
    $pdo->exec("DELETE FROM sessions");
    echo "  ✓ Deleted\n";
    
    echo "Clearing user_id from gateway_log...\n";
    $pdo->exec("UPDATE gateway_log SET user_id = NULL WHERE user_id IS NOT NULL");
    echo "  ✓ Cleared\n";
    
    echo "Deleting all users...\n";
    $pdo->exec("DELETE FROM users");
    echo "  ✓ Deleted\n";
    
    // Commit
    $pdo->commit();
    
    echo "\n=== Cleanup Complete ===\n";
    
    // Verify
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $afterCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Users after cleanup: $afterCount\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM portal_forum_link");
    $forumLinks = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Portal-Forum links: $forumLinks\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM sso_tokens");
    $ssoTokens = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "SSO tokens: $ssoTokens\n";
    
    if ($afterCount == 0) {
        echo "\n✅ SUCCESS: All users and related data have been deleted!\n";
        echo "   You can now test registration with any email.\n";
    } else {
        echo "\n⚠️  WARNING: Some users may still remain.\n";
    }
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}


