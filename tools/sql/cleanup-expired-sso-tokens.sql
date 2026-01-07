-- Cleanup: Remove expired SSO tokens
-- Run periodically (hourly/daily) via cron or manual execution
-- Example: mysql -u sinbad_user -p --socket=/tmp/mysql.sock sinbad_db < cleanup-expired-sso-tokens.sql

-- Delete expired tokens (expires_at < NOW())
DELETE FROM sso_tokens WHERE expires_at < NOW();



