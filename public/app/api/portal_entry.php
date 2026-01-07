<?php
error_log('PORTAL_ENTRY FILE EXECUTED');
/**
 * PortalEntry - Server-side entry point to XenForo
 * Portal owns the identity link; XenForo stays passive.
 */

require_once __DIR__ . '/../bootstrap.php';

session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'not_authenticated']);
    exit;
}

$portalUserId = (int) $_SESSION['user_id'];

try {
    $portalPdo = buildPortalPdo();
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'portal_db_error']);
    exit;
}

// Load Portal user (Portal owns the canonical identity)
$portalUser = fetchPortalUser($portalPdo, $portalUserId);

if (!$portalUser || empty($portalUser['public_id'])) {
    http_response_code(404);
    echo json_encode(['error' => 'user_not_found']);
    exit;
}

$publicId = $portalUser['public_id'];

// Load or create forum_user_id using portal_forum_link (Portal DB only)
$forumUserId = lookupForumUserId($portalPdo, $publicId);

if (!$forumUserId) {
    try {
        $forumUserId = provisionForumUser($portalUser);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => 'forum_user_provision_failed']);
        exit;
    }

    if (!$forumUserId) {
        http_response_code(500);
        echo json_encode(['error' => 'forum_user_provision_failed']);
        exit;
    }

    try {
        upsertForumLink($portalPdo, $publicId, $forumUserId);
        error_log('PORTAL FLOW HERE');
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'portal_forum_link_failed']);
        exit;
    }
}

// DIAGNOSTIC: check forum_user_id
error_log("DIAG: forum_user_id = " . ($forumUserId ?: 'NULL'));

// Shared secret + XF endpoint (Portal config)
$ssoConfig = require __DIR__ . '/../../config/sso/sso_config.php';
$sharedSecret = $ssoConfig['shared_secret'] ?? '';
$xfBaseUrl = $ssoConfig['xf_base'] ?? 'https://gradaeronaut.com/forum';

if ($sharedSecret === '') {
    http_response_code(500);
    echo json_encode(['error' => 'config_error']);
    exit;
}

$endpointUrl = rtrim($xfBaseUrl, '/') . '/index.php?r=portal-entry';
$postFields = http_build_query([
    'forum_user_id' => $forumUserId,
    'public_id' => $publicId, // optional, used only for trace/logging on XF side
]);

// DIAGNOSTIC: check request details
error_log("DIAG: endpointUrl = " . $endpointUrl);
error_log("DIAG: postFields = " . $postFields);
error_log("DIAG: X-Portal-Secret header = " . $sharedSecret);

$ch = curl_init();
curl_setopt(
    $ch,
    CURLOPT_URL,
    'https://gradaeronaut.com/forum/index.php?r=portal-entry'
);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-Portal-Secret: ' . $sharedSecret
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

// DIAGNOSTIC: log before request
error_log("DIAG: calling /portal-entry");
error_log('PORTAL CALL XF');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// DIAGNOSTIC: log after request
error_log("DIAG: response httpCode = " . $httpCode);
error_log("DIAG: response body = " . substr($response, 0, 200));

if ($response === false) {
    $curlError = curl_error($ch);
    curl_close($ch);
    http_response_code(502);
    echo json_encode([
        'error' => 'xf_request_failed',
        'details' => $curlError,
    ]);
    exit;
}

curl_close($ch);

if ($httpCode >= 200 && $httpCode < 300) {
    echo json_encode([
        'success' => true,
        'redirect_url' => rtrim($xfBaseUrl, '/') . '/',
    ]);
    exit;
}

http_response_code($httpCode ?: 502);
echo json_encode([
    'error' => 'portal_entry_failed',
    'http_code' => $httpCode ?: 502,
]);

/**
 * Helpers
 */
function buildPortalPdo(): PDO
{
    $config = require __DIR__ . '/../../config/db.php';
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

    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    return $pdo;
}

function fetchPortalUser(PDO $pdo, int $portalUserId): ?array
{
    $stmt = $pdo->prepare('SELECT id, public_id, username, display_name, email FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$portalUserId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function lookupForumUserId(PDO $pdo, string $publicId): ?int
{
    $stmt = $pdo->prepare('SELECT forum_user_id FROM portal_forum_link WHERE public_id = ? LIMIT 1');
    $stmt->execute([$publicId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && isset($row['forum_user_id'])) {
        $id = (int) $row['forum_user_id'];
        return $id > 0 ? $id : null;
    }

    return null;
}

function upsertForumLink(PDO $pdo, string $publicId, int $forumUserId): void
{
    $stmt = $pdo->prepare('
        INSERT INTO portal_forum_link (public_id, forum_user_id, created_at)
        VALUES (?, ?, NOW())
        ON DUPLICATE KEY UPDATE forum_user_id = VALUES(forum_user_id)
    ');
    $stmt->execute([$publicId, $forumUserId]);
}

function provisionForumUser(array $portalUser): int
{
    $xfSrcDir = realpath(__DIR__ . '/../../forum/src');
    if ($xfSrcDir === false) {
        throw new RuntimeException('xf_src_not_found');
    }

    require_once $xfSrcDir . '/XF.php';

    \XF::start($xfSrcDir);

    /** @var \XF\App $app */
    $app = \XF::setupApp('XF\Cli\App');
    $app->start();

    try {
        /** @var \XF\Repository\UserRepository $userRepo */
        $userRepo = $app->repository('XF:User');

        $email = '';
        if (!empty($portalUser['email'])) {
            $email = trim((string) $portalUser['email']);
        }

        if ($email !== '') {
            $existing = $app->finder('XF:User')->where('email', $email)->fetchOne();
            if ($existing) {
                return (int) $existing->user_id;
            }
        }

        $usernameBase = trim(
            (string) ($portalUser['display_name'] ?? $portalUser['username'] ?? '')
        );

        if ($usernameBase === '') {
            $usernameBase = 'PortalUser_' . ($portalUser['public_id'] ?? 'user');
        }

        $username = $usernameBase;
        $counter = 1;
        while ($app->finder('XF:User')->where('username', $username)->fetchOne()) {
            $username = $usernameBase . '_' . $counter;
            $counter++;
            if ($counter > 100) {
                throw new RuntimeException('username_generation_failed');
            }
        }

        /** @var \XF\Entity\User $user */
        $user = $userRepo->setupBaseUser();
        $user->username = $username;
        $user->email = $email !== '' ? $email : sprintf(
            'portal_%s@invalid.local',
            $portalUser['public_id'] ?? uniqid()
        );
        $user->user_state = 'valid';

        $auth = $user->getRelationOrDefault('Auth');
        $auth->setNoPassword();

        $user->save();

        return (int) $user->user_id;
    } finally {
        $app->stop();
    }
}

