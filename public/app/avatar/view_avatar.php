<?php
// app/avatar/view_avatar.php

// Disable error display to avoid breaking image output
ini_set('display_errors', 0);

/**
 * Output an existing image file with correct headers
 */
function serveFile($path)
{
    if (file_exists($path)) {
        $mime = mime_content_type($path);
        if ($mime) {
            header("Content-Type: $mime");
            header("Content-Length: " . filesize($path));
            readfile($path);
            exit;
        }
    }
}

/**
 * Output a dynamically generated SVG avatar with the first letter
 */
function serveSvgAvatar($text)
{
    // Use mb_substr for proper UTF-8 support (handles Cyrillic, Chinese, etc.)
    $letter = '?';
    
    if (!empty($text) && is_string($text)) {
        $text = trim($text);
        if ($text !== '') {
            $firstChar = mb_substr($text, 0, 1, 'UTF-8');
            if ($firstChar !== false && $firstChar !== '') {
                $letter = mb_strtoupper($firstChar, 'UTF-8');
            }
        }
    }

    $letterEscaped = htmlspecialchars($letter, ENT_XML1 | ENT_QUOTES, 'UTF-8');

    $svg = <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg width="256" height="256" viewBox="0 0 256 256" version="1.1" xmlns="http://www.w3.org/2000/svg">
    <rect x="0" y="0" width="256" height="256" fill="#939393"/>
    <text x="50%" y="50%" dy=".35em" text-anchor="middle" fill="#FFFFFF" font-family="Arial, sans-serif" font-size="253" font-weight="600">
        $letterEscaped
    </text>
    <text x="50%" y="50%" dy=".35em" text-anchor="middle" fill="none" font-family="Arial, sans-serif" font-size="253" font-weight="600" stroke="#4A5568" stroke-width="7" stroke-linejoin="round">
        $letterEscaped
    </text>
</svg>
SVG;

    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Content-Type: image/svg+xml');
    echo $svg;
    exit;
}

// 1. Get user ID from request
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($userId <= 0) {
    // Invalid or missing ID, serve default avatar
    serveSvgAvatar('?');
    exit;
}

// 2. Try to load DB config
$pdo = null;
try {
    if (file_exists(__DIR__ . '/../../config/db.php')) {
        $config = require __DIR__ . '/../../config/db.php';
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['username'], $config['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
} catch (Throwable $e) {
    // DB error, fallback to SVG avatar
    serveSvgAvatar('?');
    exit;
}

// 3. Get user from database
$user = null;
if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT id, display_name, username, public_id FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        // DB error, fallback to SVG avatar
        serveSvgAvatar('?');
        exit;
    }
}

// 4. If user not found, serve default avatar
if (!$user) {
    serveSvgAvatar('?');
    exit;
}

// 5. Check if uploaded avatar file exists
$avatarPath = __DIR__ . "/../../uploads/avatars/{$userId}.png";
if (file_exists($avatarPath)) {
    serveFile($avatarPath);
    exit;
}

// 6. Fallback: Generate SVG avatar with first letter
// Priority: display_name > username > public_id
$displayText = null;

// Check display_name (can be NULL in DB)
if (isset($user['display_name']) && $user['display_name'] !== null) {
    $trimmed = trim($user['display_name']);
    if ($trimmed !== '') {
        $displayText = $trimmed;
    }
}

// Fallback to username if display_name is empty/null
if ($displayText === null && isset($user['username']) && $user['username'] !== null) {
    $trimmed = trim($user['username']);
    if ($trimmed !== '') {
        $displayText = $trimmed;
    }
}

// Fallback to public_id if both are empty/null
if ($displayText === null && isset($user['public_id']) && $user['public_id'] !== null) {
    $displayText = $user['public_id'];
}

// Final fallback
if ($displayText === null || $displayText === '') {
    $displayText = '?';
}

serveSvgAvatar($displayText);
