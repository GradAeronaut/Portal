<?php
// Simple GitHub webhook deploy

$secret = getenv('DEPLOY_SECRET'); // тот же секрет в GitHub
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

if ($secret) {
    $hash = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    if (!hash_equals($hash, $signature)) {
        http_response_code(403);
        exit('Invalid signature');
    }
}

chdir(__DIR__);
exec('git config --global --add safe.directory ' . __DIR__);
exec('git fetch origin main 2>&1');
exec('git reset --hard origin/main 2>&1');

http_response_code(200);
echo 'OK';


