<?php

/**
 * One-time migration script to introduce users.public_id (CHAR(6)) and
 * remove legacy external_id usage.
 *
 * Steps:
 *  - Add nullable public_id column if it does not exist yet
 *  - Generate unique 6-char public_id for all users where it is NULL
 *    using PublicId::generate()
 *  - Make public_id NOT NULL and UNIQUE
 *  - Drop external_id column and its index if they still exist
 *
 * Run from CLI:
 *   php tools/migrate_public_id.php
 */

use PDO;
use PDOException;

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../php/PublicId.php';

if (PHP_SAPI !== 'cli') {
    echo "This script must be run from CLI.\n";
    exit(1);
}

echo "== Starting public_id migration ==\n";

$dbConfig = require __DIR__ . '/../config/db.php';
$charset  = $dbConfig['charset'] ?? 'utf8mb4';

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
} catch (PDOException $e) {
    echo "DB connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Check if public_id column already exists
$columnCheck = $pdo->query("SHOW COLUMNS FROM users LIKE 'public_id'")->fetch(PDO::FETCH_ASSOC);
if (!$columnCheck) {
    echo "Adding nullable public_id column to users...\n";
    $pdo->exec("ALTER TABLE users ADD COLUMN public_id CHAR(6) NULL AFTER id");
} else {
    echo "public_id column already exists, skipping ADD COLUMN.\n";
}

// Backfill public_id for existing users
echo "Backfilling public_id for existing users...\n";

$pdo->beginTransaction();
try {
    $selectStmt = $pdo->query("SELECT id FROM users WHERE public_id IS NULL");
    $updateStmt = $pdo->prepare("UPDATE users SET public_id = ? WHERE id = ?");

    $count = 0;
    while ($row = $selectStmt->fetch(PDO::FETCH_ASSOC)) {
        $userId = (int)$row['id'];
        $publicId = PublicId::generate($pdo);
        $updateStmt->execute([$publicId, $userId]);
        $count++;
    }

    $pdo->commit();
    echo "Backfilled public_id for {$count} users.\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    echo "Error during backfill: " . $e->getMessage() . "\n";
    exit(1);
}

// Make public_id NOT NULL and UNIQUE
echo "Enforcing NOT NULL and UNIQUE on public_id...\n";
$pdo->exec("ALTER TABLE users MODIFY public_id CHAR(6) NOT NULL");

// Add unique index if not present
$indexCheck = $pdo->query("SHOW INDEX FROM users WHERE Key_name = 'public_id'")->fetch(PDO::FETCH_ASSOC);
if (!$indexCheck) {
    $pdo->exec("ALTER TABLE users ADD UNIQUE KEY public_id (public_id)");
    echo "Added UNIQUE index on public_id.\n";
} else {
    echo "UNIQUE index on public_id already exists, skipping.\n";
}

// Drop legacy external_id column and index if present
$externalCol = $pdo->query("SHOW COLUMNS FROM users LIKE 'external_id'")->fetch(PDO::FETCH_ASSOC);
if ($externalCol) {
    echo "Dropping legacy external_id column and index...\n";
    // Drop index if it exists
    $extIndex = $pdo->query("SHOW INDEX FROM users WHERE Key_name = 'external_id'")->fetch(PDO::FETCH_ASSOC);
    if ($extIndex) {
        $pdo->exec("ALTER TABLE users DROP KEY external_id");
    }
    $pdo->exec("ALTER TABLE users DROP COLUMN external_id");
} else {
    echo "external_id column not found, nothing to drop.\n";
}

echo "== public_id migration completed successfully ==\n";






