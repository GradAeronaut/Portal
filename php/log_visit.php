<?php

function log_visit(string $publicId, string $page): void
{
    if ($publicId === '') {
        return;
    }

    $config = require __DIR__ . '/../config/db.php';

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['host'],
        $config['port'] ?? 3306,
        $config['dbname'],
        $config['charset'] ?? 'utf8mb4'
    );

    try {
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    } catch (Throwable $e) {
        return;
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $stmt = $pdo->prepare("
        INSERT INTO user_visits (public_id, page, ip, user_agent)
        VALUES (?, ?, INET6_ATON(?), ?)
    ");

    try {
        $stmt->execute([$publicId, $page, $ip, $ua]);
    } catch (Throwable $e) {
        // silent — logger must not break the site
    }
}






