<?php
/**
 * Google OAuth2 - Start
 * Инициализация процесса аутентификации через Google
 */

session_start();

// Загружаем конфигурацию Google OAuth2
$google_config = require __DIR__ . '/../config/google_oauth.php';

$google_client_id = $google_config['client_id'];
$google_client_secret = $google_config['client_secret'];
$redirect_uri = $google_config['redirect_uri'];

// Scopes для запроса
$google_scopes = 'openid email profile';

// Игнорируем параметр next - всегда редиректим на Shape
$_SESSION['oauth_next'] = '/shape-sinbad/';

// Генерируем state для защиты от CSRF
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

// Параметры для Google OAuth2
$params = [
    'client_id' => $google_client_id,
    'redirect_uri' => $redirect_uri,
    'response_type' => 'code',
    'scope' => $google_scopes,
    'state' => $state,
    'access_type' => 'online',
    'prompt' => 'select_account'
];

// Формируем URL для редиректа
$auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);

// Редиректим пользователя на Google
header('Location: ' . $auth_url);
exit;
?>

