<?php
/**
 * Cleanup expired SSO tokens
 * 
 * Run via cron:
 * 0 * * * * /usr/bin/php /var/www/gradaeronaut.com/tools/cleanup-expired-sso-tokens.php
 * 
 * Or daily:
 * 0 2 * * * /usr/bin/php /var/www/gradaeronaut.com/tools/cleanup-expired-sso-tokens.php
 */

require_once __DIR__ . '/../config/db.php';

$dbConfig = require __DIR__ . '/../config/db.php';
$charset = $dbConfig['charset'] ?? 'utf8mb4';

if (!empty($dbConfig['socket'])) {
    $dsn = sprintf(
        'mysql:unix_socket=%s;dbname=%s;charset=%s',
        $dbConfig['socket'],
        $dbConfig['dbname'],
        $charset
    );
} else {
    $host = $dbConfig['host'] ?? '127.0.0.1';
    $port = (int) ($dbConfig['port'] ?? 3306);
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $host,
        $port,
        $dbConfig['dbname'],
        $charset
    );
}

try {
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Delete expired tokens
    $stmt = $pdo->prepare("DELETE FROM sso_tokens WHERE expires_at < NOW()");
    $stmt->execute();
    $deletedCount = $stmt->rowCount();
    
    if (php_sapi_name() === 'cli') {
        echo "Cleaned up {$deletedCount} expired SSO tokens\n";
    }
    
} catch (PDOException $e) {
    if (php_sapi_name() === 'cli') {
        error_log("SSO token cleanup failed: " . $e->getMessage());
        exit(1);
    }
    // Silent fail in web context
}



