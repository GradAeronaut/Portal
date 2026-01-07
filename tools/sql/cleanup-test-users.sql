-- ============================================================
-- Complete Cleanup of All Portal Users
-- Removes ALL previously registered users and ALL their data
-- ============================================================
-- 
-- This script removes ALL users and their related data:
-- - All users from users table (emails, usernames, display_names)
-- - All portal_forum_link entries
-- - All portal_kneeboard_link entries
-- - All sso_tokens entries
-- - All sessions entries (CASCADE will handle this automatically)
-- - All user_2fa entries (CASCADE will handle this automatically)
-- - All gateway_log entries (user_id will be set to NULL)
--
-- WARNING: This script deletes ALL users and ALL their data!
-- This is a complete cleanup for testing purposes.
-- Do not run on production!
-- ============================================================

-- Start transaction for safety
START TRANSACTION;

-- Step 1: Delete all portal_forum_link entries
-- (This table links portal users to forum users)
DELETE FROM portal_forum_link;

-- Step 2: Delete all portal_kneeboard_link entries
-- (This table links portal users to kneeboard users)
DELETE FROM portal_kneeboard_link;

-- Step 3: Delete all sso_tokens entries
-- (SSO tokens for authentication)
DELETE FROM sso_tokens;

-- Step 4: Delete all sessions entries
-- (User session tokens - CASCADE will also handle this when users are deleted)
DELETE FROM sessions;

-- Step 5: Clear user_id from gateway_log (set to NULL)
-- (Log entries - we keep the logs but remove user references)
UPDATE gateway_log SET user_id = NULL WHERE user_id IS NOT NULL;

-- Step 6: Delete all users
-- This will also CASCADE delete:
-- - sessions (via foreign key CASCADE)
-- - user_2fa (via foreign key CASCADE)
DELETE FROM users;

-- Verify deletion (optional - uncomment to check)
-- SELECT COUNT(*) as remaining_users FROM users;
-- SELECT COUNT(*) as remaining_forum_links FROM portal_forum_link;
-- SELECT COUNT(*) as remaining_sso_tokens FROM sso_tokens;

-- Commit transaction
COMMIT;

-- ============================================================
-- Notes:
-- - After running this script, ALL users are deleted
-- - All emails, usernames, and display_names are cleared
-- - Registration with any email will work and create a new user
-- - New users will get new public_id values
-- - This allows testing XF user creation from scratch
-- ============================================================

