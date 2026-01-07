<?php

/**
 * Portal ↔ KNEEBOARD user linking endpoint
 *
 * POST /app/api/link_kneeboard_user.php
 * Content-Type: application/json
 *
 * Request:
 * {
 *   "public_id": "ABC123",
 *   "kneeboard_user_id": 57
 * }
 *
 * Success response:
 * {
 *   "ok": true,
 *   "public_id": "ABC123",
 *   "kneeboard_user_id": 57
 * }
 *
 * Error responses:
 *   400 Bad Request  - missing/invalid fields, unknown public_id
 *   405 Method Not Allowed - non-POST method
 *   409 Conflict     - kneeboard_user_id already linked to another public_id
 *   500 Server Error - database errors
 */

header('Content-Type: application/json');

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

// Read and decode JSON body
$rawBody = file_get_contents('php://input');
$input   = json_decode($rawBody, true);

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_json']);
    exit;
}

// Basic validation
if (!array_key_exists('public_id', $input) || !array_key_exists('kneeboard_user_id', $input)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'missing_fields']);
    exit;
}

$publicId = $input['public_id'];
$kneeboardUserId = $input['kneeboard_user_id'];

// Validate types
if (!is_string($publicId)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_public_id_type']);
    exit;
}

// Normalize and validate public_id: 6 characters, typically A-Z0-9
$publicId = trim($publicId);
if (strlen($publicId) !== 6 || !preg_match('/^[A-Z0-9]+$/', $publicId)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_public_id_format']);
    exit;
}

// kneeboard_user_id must be an integer > 0 (accept numeric strings)
if (is_int($kneeboardUserId)) {
    $kneeboardUserIdInt = $kneeboardUserId;
} elseif (is_string($kneeboardUserId) && ctype_digit($kneeboardUserId)) {
    $kneeboardUserIdInt = (int) $kneeboardUserId;
} else {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_kneeboard_user_id_type']);
    exit;
}

if ($kneeboardUserIdInt <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_kneeboard_user_id_value']);
    exit;
}

// Load database configuration
$dbConfig = require __DIR__ . '/../../config/db.php';
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
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'db_connection_failed']);
    exit;
}

// 1) Ensure public_id exists in users table
try {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE public_id = ? LIMIT 1');
    $stmt->execute([$publicId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'db_query_failed']);
    exit;
}

if (!$user) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'public_id_not_found']);
    exit;
}

// 2) Check existing links for this public_id or kneeboard_user_id
try {
    $stmt = $pdo->prepare('SELECT public_id, kneeboard_user_id FROM portal_kneeboard_link WHERE public_id = ? OR kneeboard_user_id = ? LIMIT 1');
    $stmt->execute([$publicId, $kneeboardUserIdInt]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'db_query_failed']);
    exit;
}

if ($existing) {
    // Same mapping already exists -> idempotent success
    if ($existing['public_id'] === $publicId && (int)$existing['kneeboard_user_id'] === $kneeboardUserIdInt) {
        echo json_encode([
            'ok' => true,
            'public_id' => $publicId,
            'kneeboard_user_id' => $kneeboardUserIdInt,
        ]);
        exit;
    }

    // kneeboard_user_id is already linked to a different public_id
    if ((int)$existing['kneeboard_user_id'] === $kneeboardUserIdInt && $existing['public_id'] !== $publicId) {
        http_response_code(409);
        echo json_encode([
            'ok' => false,
            'error' => 'kneeboard_user_id_already_linked',
            'existing_public_id' => $existing['public_id'],
        ]);
        exit;
    }

    // public_id is already linked to a different kneeboard_user_id
    if ($existing['public_id'] === $publicId && (int)$existing['kneeboard_user_id'] !== $kneeboardUserIdInt) {
        http_response_code(409);
        echo json_encode([
            'ok' => false,
            'error' => 'public_id_already_linked',
            'existing_kneeboard_user_id' => (int)$existing['kneeboard_user_id'],
        ]);
        exit;
    }
}

// 3) Insert new link
try {
    $insert = $pdo->prepare('INSERT INTO portal_kneeboard_link (public_id, kneeboard_user_id) VALUES (?, ?)');
    $insert->execute([$publicId, $kneeboardUserIdInt]);
} catch (PDOException $e) {
    // Handle unique constraint race conditions gracefully
    if ((int)$e->getCode() === 23000) { // Integrity constraint violation
        http_response_code(409);
        echo json_encode(['ok' => false, 'error' => 'unique_constraint_violation']);
    } else {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'db_insert_failed']);
    }
    exit;
}

echo json_encode([
    'ok' => true,
    'public_id' => $publicId,
    'kneeboard_user_id' => $kneeboardUserIdInt,
]);






