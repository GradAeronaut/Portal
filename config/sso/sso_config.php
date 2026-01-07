<?php
/**
 * SSO Configuration
 * Shared configuration between Sinbad Portal and XenForo
 */

return [
    // Shared secret for HMAC SHA256 signing (64 hex characters)
    // IMPORTANT: Change this to a unique random value in production!
    'shared_secret' => 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2',
    
    // Token expiration time in seconds (default: 5 minutes)
    'token_lifetime' => 300,
    
    // Portal base URL
    'portal_base' => 'https://gradaeronaut.com',
    'portal_url' => 'https://gradaeronaut.com',
    
    // XenForo base URL
    'xf_base' => 'https://gradaeronaut.com/forum',
    'xenforo_url' => 'https://gradaeronaut.com/forum',
    
    // SSO endpoints
    'auth_endpoint' => '/app/sso/xf_auth.php',
    'validate_endpoint' => '/app/sso/xf_validate_sso_token.php',
    'token_endpoint' => '/app/sso/xf_generate_token.php',
    
    // Legacy endpoints array (kept for backward compatibility)
    'endpoints' => [
        'auth' => '/app/sso/xf_auth.php',
        'generate_token' => '/app/sso/xf_generate_token.php',
        'validate_token' => '/app/sso/xf_validate_sso_token.php',
    ]
];

