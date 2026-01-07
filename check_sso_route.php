<?php
/**
 * Check SSO route in xf_route table (read-only)
 */
require_once __DIR__ . '/config/db.php';

$dbConfig = require __DIR__ . '/config/db.php';
$charset = $dbConfig['charset'] ?? 'utf8mb4';

if (!empty($dbConfig['socket'])) {
    $dsn = sprintf(
        'mysql:unix_socket=%s;dbname=%s;charset=%s',
        $dbConfig['socket'],
        'sinbad_db', // XenForo DB name
        $charset
    );
} else {
    $host = $dbConfig['host'] ?? '127.0.0.1';
    $port = (int) ($dbConfig['port'] ?? 3306);
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $host,
        $port,
        'sinbad_db', // XenForo DB name
        $charset
    );
}

try {
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("
        SELECT route_id, route_prefix, controller, addon_id
        FROM xf_route
        WHERE route_type = 'public'
        AND route_prefix LIKE '%sso%'
    ");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($results)) {
        echo "No SSO routes found in xf_route.\n";
    } else {
        foreach ($results as $row) {
            printf("route_id: %s, route_prefix: %s, controller: %s, addon_id: %s\n",
                $row['route_id'] ?? 'NULL',
                $row['route_prefix'] ?? 'NULL',
                $row['controller'] ?? 'NULL',
                $row['addon_id'] ?? 'NULL'
            );
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}


