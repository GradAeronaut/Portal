<?php
/**
 * Скрипт для проверки пользователя с именем "gradov"
 */

require_once __DIR__ . '/../config/db.php';

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Поиск пользователя по разным полям
    echo "=== Searching for user with 'gradov' ===\n\n";
    
    // По display_name
    $stmt = $pdo->prepare("SELECT id, display_name, username, public_id, email FROM users WHERE display_name = ? OR display_name LIKE ?");
    $stmt->execute(['gradov', '%gradov%']);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($rows) {
        echo "Found by display_name:\n";
        foreach ($rows as $row) {
            print_r($row);
        }
    }
    
    // По username
    $stmt = $pdo->prepare("SELECT id, display_name, username, public_id, email FROM users WHERE username = ? OR username LIKE ?");
    $stmt->execute(['gradov', '%gradov%']);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($rows) {
        echo "\nFound by username:\n";
        foreach ($rows as $row) {
            print_r($row);
        }
    }
    
    // По public_id
    $stmt = $pdo->prepare("SELECT id, display_name, username, public_id, email FROM users WHERE public_id = ?");
    $stmt->execute(['gradov']);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($rows) {
        echo "\nFound by public_id:\n";
        foreach ($rows as $row) {
            print_r($row);
        }
    }
    
    // Все пользователи (первые 10)
    echo "\n=== All users (first 10) ===\n";
    $stmt = $pdo->query("SELECT id, display_name, username, public_id FROM users LIMIT 10");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        echo "ID: {$row['id']}, display_name: " . ($row['display_name'] ?: 'NULL') . ", username: " . ($row['username'] ?: 'NULL') . ", public_id: {$row['public_id']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

