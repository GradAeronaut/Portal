-- SSO Integration - Token Storage Table
-- This table stores one-time tokens for SSO authentication between Sinbad Portal and XenForo
-- Run this migration: mysql -u sinbad_user -p111111 --socket=/tmp/mysql.sock sinbad_db < migration-sso-tokens.sql

CREATE TABLE IF NOT EXISTS sso_tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(128) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_token (token),
  INDEX idx_user_id (user_id),
  INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Clean up expired tokens (run periodically)
-- DELETE FROM sso_tokens WHERE expires_at < NOW();


