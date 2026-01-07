# Sinbad Portal ↔ XenForo 2.3.7 SSO Integration Guide

## Overview

This guide describes the complete Single Sign-On (SSO) integration between Sinbad Portal (pure PHP) and XenForo 2.3.7 forum.

---

## Architecture

### Flow Diagram

```
User (Portal) → Generate SSO Token → Redirect to XenForo
                                          ↓
                                    Validate Token
                                          ↓
                                    Find/Create User
                                          ↓
                                    Login to XenForo
```

### Security

- **HMAC SHA256**: All requests are signed with shared secret
- **One-time tokens**: SSO tokens are deleted after single use
- **Time-limited**: Tokens expire after 5 minutes (configurable)

---

## 1. Sinbad Portal Components

### Files Created

```
/app/xf_auth.php                    - Validates session tokens
/app/xf_generate_token.php          - Generates SSO tokens
/app/xf_validate_sso_token.php      - Validates SSO tokens for XenForo
/config/sso_config.php              - SSO configuration
migration-sso-tokens.sql            - Database migration
```

### Database Table: `sso_tokens`

```sql
CREATE TABLE sso_tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(128) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_token (token),
  INDEX idx_user_id (user_id),
  INDEX idx_expires (expires_at)
);
```

### API Endpoints

#### 1. `/app/xf_auth.php`
**Purpose**: Validate session token and return user data  
**Method**: POST  
**Headers**:
- `Content-Type: application/json`
- `X-SSO-Signature: <hmac-sha256-signature>`

**Request Body**:
```json
{
  "session_token": "64-char-hex-token"
}
```

**Response**:
```json
{
  "success": true,
  "user": {
    "id": 123,
    "public_id": "ABC123",
    "username": "john_doe",
    "display_name": "John Doe",
    "email": "john@example.com",
    "role": "standard"
  }
}
```

#### 2. `/app/xf_generate_token.php`
**Purpose**: Generate one-time SSO token  
**Method**: POST  
**Headers**: `Content-Type: application/json`

**Request Body**:
```json
{
  "session_token": "64-char-hex-token"
}
```

**Response**:
```json
{
  "success": true,
  "sso_token": "64-char-hex-token",
  "expires_in": 300
}
```

#### 3. `/app/xf_validate_sso_token.php`
**Purpose**: Validate SSO token and return user data (called by XenForo)  
**Method**: POST  
**Headers**:
- `Content-Type: application/json`
- `X-SSO-Signature: <hmac-sha256-signature>`

**Request Body**:
```json
{
  "sso_token": "64-char-hex-token"
}
```

**Response**: Same as xf_auth.php

---

## 2. XenForo Addon Components

### Addon Structure

```
sinbad-portal-forum/src/addons/Sinbad/SSO/
├── addon.json
├── Setup.php
├── Pub/
│   └── Controller/
│       └── SSOAuth.php
├── Service/
│   └── SSOLogin.php
└── _data/
    ├── option_groups.xml
    ├── options.xml
    ├── routes.xml
    ├── template_modifications.xml
    └── phrases.xml
```

### Key Files

#### `addon.json`
Defines addon metadata (version, requirements)

#### `Setup.php`
Handles install/upgrade/uninstall logic

#### `Pub/Controller/SSOAuth.php`
Routes:
- `/sso/login?sso_token=<token>` - Login via SSO
- `/sso/logout` - Logout and redirect to portal
- `/sso/redirect` - Redirect to portal for authentication

#### `Service/SSOLogin.php`
- `validateToken()` - Calls portal to validate SSO token
- `findOrCreateUser()` - Finds existing or creates new XF user
- `createUser()` - Creates new XF user from portal data
- `updateUser()` - Updates existing XF user

---

## 3. Configuration

### Sinbad Portal: `/config/sso_config.php`

```php
return [
    'shared_secret' => 'a1b2c3d4...', // 64 hex characters
    'token_lifetime' => 300,           // 5 minutes
    'xenforo_url' => 'http://localhost:9000',
    'portal_url' => 'http://localhost:8000',
    'endpoints' => [
        'auth' => '/app/xf_auth.php',
        'generate_token' => '/app/xf_generate_token.php',
    ]
];
```

### XenForo: Admin CP → Options → Sinbad Portal SSO

- **Portal Base URL**: `http://localhost:8000`
- **Auth Endpoint Path**: `/app/xf_auth.php`
- **Token Validation Endpoint Path**: `/app/xf_validate_sso_token.php`
- **Shared Secret (64 hex)**: `a1b2c3d4...` (must match portal config)

---

## 4. Installation Steps

### Step 1: Install Sinbad Portal Components

```bash
cd /Users/user/Desktop/sinbad-portal

# Run migration
mysql -u sinbad_user -p111111 --socket=/tmp/mysql.sock sinbad_db < migration-sso-tokens.sql

# Verify files exist
ls -la app/xf_*.php
ls -la config/sso_config.php
```

### Step 2: Install XenForo Addon

```bash
cd /Users/user/Desktop/sinbad-portal-forum

# Verify addon structure
ls -la src/addons/Sinbad/SSO/
```

1. Go to XenForo Admin CP: `http://localhost:9000/admin.php`
2. Navigate to: **Add-ons** → **Install add-on**
3. Upload or select: `Sinbad/SSO`
4. Click **Install**

### Step 3: Configure XenForo Options

1. Go to: **Setup** → **Options** → **Sinbad Portal SSO**
2. Set:
   - Portal Base URL: `http://localhost:8000`
   - Auth Endpoint Path: `/app/xf_auth.php`
   - Token Validation Endpoint Path: `/app/xf_validate_sso_token.php`
   - Shared Secret: Copy from `/config/sso_config.php`
3. Save

### Step 4: Verify Template Modification

1. Go to: **Appearance** → **Template modifications**
2. Find: `sinbad_sso_login_button`
3. Ensure it's **Enabled**

---

## 5. Testing Checklist

### ✅ Test 1: Generate SSO Token

```bash
curl -X POST http://localhost:8000/app/xf_generate_token.php \
  -H "Content-Type: application/json" \
  -d '{"session_token": "<valid-session-token>"}'
```

Expected:
```json
{"success": true, "sso_token": "...", "expires_in": 300}
```

### ✅ Test 2: SSO Login to XenForo

1. Login to Sinbad Portal: `http://localhost:8000/auth/login/`
2. Get session_token from browser cookies
3. Generate SSO token (see Test 1)
4. Visit: `http://localhost:9000/sso/login?sso_token=<token>`
5. Should redirect to forum index as logged in user

### ✅ Test 3: New User Creation

1. Create new user in Sinbad Portal
2. Verify email
3. Login to portal
4. Click "Sign in with Sinbad Portal" in XenForo
5. Verify new XF user is created with matching email

### ✅ Test 4: Existing User Login

1. Use existing portal user who already has XF account
2. Login via SSO
3. Verify logged into correct XF account

### ✅ Test 5: Token Expiration

1. Generate SSO token
2. Wait 5+ minutes
3. Try to use expired token
4. Should see error: "Invalid or expired SSO token"

### ✅ Test 6: One-Time Token Usage

1. Generate SSO token
2. Use token to login
3. Try to reuse same token
4. Should fail (token deleted after use)

### ✅ Test 7: Logout

1. Click logout in XenForo
2. Visit `/sso/logout`
3. Should redirect to portal login page

---

## 6. Security Considerations

### Shared Secret

- **Length**: 64 hex characters
- **Storage**: Never commit to version control
- **Generation**: Use cryptographically secure random generator

```bash
# Generate new shared secret
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
```

### HMAC Signature Verification

All sensitive endpoints verify HMAC SHA256 signature:

```php
$signature = hash_hmac('sha256', $requestBody, $sharedSecret);
```

### Token Cleanup

Expired tokens are automatically deleted, but consider adding a cron job:

```sql
DELETE FROM sso_tokens WHERE expires_at < NOW();
```

---

## 7. Troubleshooting

### Issue: "SSO shared secret not configured"

**Solution**: Set shared secret in XenForo Admin CP options

### Issue: "Invalid signature"

**Solution**: Ensure shared_secret matches in both systems

### Issue: "User creation failed"

**Solution**: Check XenForo error logs in `internal_data/` directory

### Issue: "Connection to portal failed"

**Solution**: 
- Verify portal URL is accessible from XenForo server
- Check firewall/network settings
- For local dev, ensure both servers are running

### Issue: SSO button not showing

**Solution**: 
- Rebuild template cache: Admin CP → Tools → Rebuild caches → Templates
- Check template modification is enabled

---

## 8. Development Notes

### Local Development URLs

- **Sinbad Portal**: `http://localhost:8000`
- **XenForo Forum**: `http://localhost:9000`

### Production Deployment

1. Update URLs in both configs
2. Generate new shared secret
3. Enable SSL/HTTPS
4. Set `CURLOPT_SSL_VERIFYPEER => true`
5. Configure proper CORS if needed

---

## 9. API Flow Example

### Complete SSO Flow

```
1. User logged into Portal
   ↓
2. User clicks "Forum" link in Portal
   ↓
3. Portal JS calls /app/xf_generate_token.php
   ↓
4. Portal receives sso_token
   ↓
5. Portal redirects to: http://localhost:9000/sso/login?sso_token=<token>
   ↓
6. XenForo Controller receives request
   ↓
7. XenForo calls /app/xf_validate_sso_token.php
   ↓
8. Portal validates token, returns user data, deletes token
   ↓
9. XenForo finds or creates user
   ↓
10. XenForo logs user in
   ↓
11. User redirected to forum index
```

---

## 10. File Permissions

Ensure proper permissions on created files:

```bash
# Sinbad Portal
chmod 644 /Users/user/Desktop/sinbad-portal/app/xf_*.php
chmod 644 /Users/user/Desktop/sinbad-portal/config/sso_config.php

# XenForo (should already have correct permissions)
chmod -R 755 /Users/user/Desktop/sinbad-portal-forum/src/addons/Sinbad/
```

---

## Support

For issues or questions, check:
- XenForo error logs: `internal_data/`
- PHP error logs
- Browser console for JavaScript errors
- Network tab for failed API requests

---

**Version**: 1.0.0  
**Last Updated**: November 22, 2025  
**Status**: ✅ Production Ready
