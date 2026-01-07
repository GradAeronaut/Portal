<?php
/**
 * Environment-aware DB configuration bootstrapper.
 * If the current host is localhost or 127.0.0.1 we load db.local.php,
 * otherwise we fall back to db.prod.php.
 */

$hostHeader = null;
foreach (['HTTP_HOST', 'SERVER_NAME', 'SERVER_ADDR'] as $key) {
    if (!empty($_SERVER[$key])) {
        $hostHeader = (string) $_SERVER[$key];
        break;
    }
}

if ($hostHeader === null && PHP_SAPI === 'cli') {
    $hostHeader = 'localhost';
}

$normalizedHost = $hostHeader ? strtolower($hostHeader) : '';
if (strpos($normalizedHost, ':') !== false) {
    $normalizedHost = explode(':', $normalizedHost, 2)[0];
}

$isLocalHost = in_array($normalizedHost, ['localhost', '127.0.0.1'], true);

$configFile = __DIR__ . ($isLocalHost ? '/db.local.php' : '/db.prod.php');

return require $configFile;

