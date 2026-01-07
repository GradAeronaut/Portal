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
    
    // XenForo base URL
    'xenforo_url' => 'https://gradaeronaut.com/forum',
    
    // Sinbad Portal base URL
    'portal_url' => 'https://gradaeronaut.com',
    
    // SSO endpoints
    'endpoints' => [
        'auth' => '/app/xf_auth.php',
        'generate_token' => '/app/xf_generate_token.php',
    ]
];


