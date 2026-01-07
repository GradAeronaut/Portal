# SSO Integration - Files Checklist

## ✅ Sinbad Portal Files

### Configuration
- [x] `/config/sso_config.php` - SSO shared configuration
- [x] `migration-sso-tokens.sql` - Database migration (EXECUTED)

### API Endpoints
- [x] `/app/xf_auth.php` - Session token validation
- [x] `/app/xf_generate_token.php` - SSO token generation
- [x] `/app/xf_validate_sso_token.php` - SSO token validation (for XenForo)

### Documentation
- [x] `SSO_INTEGRATION_GUIDE.md` - Complete integration guide
- [x] `SSO_FILES_CHECKLIST.md` - This file

---

## ✅ XenForo Addon Files

### Addon Root
- [x] `/src/addons/Sinbad/SSO/addon.json` - Addon metadata
- [x] `/src/addons/Sinbad/SSO/Setup.php` - Install/upgrade handler

### Controllers
- [x] `/src/addons/Sinbad/SSO/Pub/Controller/SSOAuth.php` - SSO routes handler

### Services
- [x] `/src/addons/Sinbad/SSO/Service/SSOLogin.php` - SSO business logic

### Data Files (_data/)
- [x] `/src/addons/Sinbad/SSO/_data/option_groups.xml` - Admin options group
- [x] `/src/addons/Sinbad/SSO/_data/options.xml` - Admin options definitions
- [x] `/src/addons/Sinbad/SSO/_data/routes.xml` - URL routes (/sso/*)
- [x] `/src/addons/Sinbad/SSO/_data/template_modifications.xml` - Login button
- [x] `/src/addons/Sinbad/SSO/_data/phrases.xml` - Error messages

---

## 🔧 Configuration Required

### 1. XenForo Admin CP (http://localhost:9000/admin.php)

Go to: **Setup → Options → Sinbad Portal SSO**

Set these values:
```
Portal Base URL:              http://localhost:8000
Auth Endpoint Path:           /app/xf_auth.php
Token Validation Endpoint:    /app/xf_validate_sso_token.php
Shared Secret:                a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2
```

### 2. Shared Secret

**IMPORTANT**: Both systems use the same shared secret.

**Portal**: `/config/sso_config.php` → `shared_secret`  
**XenForo**: Admin CP → Options → `sinbadSSOSharedSecret`

**Current Value** (CHANGE IN PRODUCTION):
```
a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2
```

---

## 🚀 Installation Commands

### Sinbad Portal
```bash
cd /Users/user/Desktop/sinbad-portal

# Already executed ✅
mysql -u sinbad_user -p111111 --socket=/tmp/mysql.sock sinbad_db < migration-sso-tokens.sql
```

### XenForo Addon
1. Admin CP → Add-ons → Install add-on
2. Select: **Sinbad/SSO**
3. Click: **Install**

---

## 🧪 Quick Test

### Test Token Generation
```bash
# Get session token from browser cookie after logging into portal
SESSION_TOKEN="your-session-token-here"

curl -X POST http://localhost:8000/app/xf_generate_token.php \
  -H "Content-Type: application/json" \
  -d "{\"session_token\": \"$SESSION_TOKEN\"}"
```

Expected output:
```json
{
  "success": true,
  "sso_token": "64-char-hex-string",
  "expires_in": 300
}
```

### Test SSO Login
```bash
# Use sso_token from above
SSO_TOKEN="sso-token-from-above"

# Visit in browser:
open "http://localhost:9000/sso/login?sso_token=$SSO_TOKEN"
```

Should redirect to forum index as logged in user.

---

## 📊 Verification Steps

### ✅ Step 1: Verify Portal Files
```bash
ls -la /Users/user/Desktop/sinbad-portal/app/xf_*.php
ls -la /Users/user/Desktop/sinbad-portal/config/sso_config.php
```

### ✅ Step 2: Verify Database Table
```bash
mysql -u sinbad_user -p111111 --socket=/tmp/mysql.sock sinbad_db -e "DESCRIBE sso_tokens;"
```

### ✅ Step 3: Verify XenForo Addon Structure
```bash
find /Users/user/Desktop/sinbad-portal-forum/src/addons/Sinbad/SSO/ -type f
```

### ✅ Step 4: Verify XenForo Addon Installation
1. Go to: http://localhost:9000/admin.php
2. Navigate to: **Add-ons**
3. Look for: **Sinbad Portal SSO** (version 1.0.0)

### ✅ Step 5: Verify Template Modification
1. Go to: **Appearance → Template modifications**
2. Look for: `sinbad_sso_login_button`
3. Status should be: **Enabled**

### ✅ Step 6: Verify SSO Button on Login Page
1. Logout from XenForo
2. Go to: http://localhost:9000/login/
3. Should see: **"Sign in with Sinbad Portal"** button above username field

---

## 🔍 Debugging

### Check XenForo Error Logs
```bash
tail -f /Users/user/Desktop/sinbad-portal-forum/internal_data/error_log_*.log
```

### Check PHP Server Logs
```bash
# If running PHP built-in server for portal
# Logs appear in terminal where server is running
```

### Test Endpoints Directly

#### Test xf_auth.php
```bash
SESSION_TOKEN="your-valid-session-token"
REQUEST_BODY="{\"session_token\": \"$SESSION_TOKEN\"}"
SIGNATURE=$(echo -n "$REQUEST_BODY" | openssl dgst -sha256 -hmac "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2" | cut -d' ' -f2)

curl -X POST http://localhost:8000/app/xf_auth.php \
  -H "Content-Type: application/json" \
  -H "X-SSO-Signature: $SIGNATURE" \
  -d "$REQUEST_BODY"
```

---

## 📝 Notes

- All files created with proper PHP/XML syntax
- No placeholders or TODOs
- Ready for production use (after changing shared secret)
- Database migration already executed
- XenForo addon ready to install

---

## 🎯 Next Steps

1. **Install XenForo Addon**
   - Admin CP → Add-ons → Install add-on → Select Sinbad/SSO

2. **Configure Options**
   - Admin CP → Setup → Options → Sinbad Portal SSO
   - Enter all configuration values

3. **Test SSO Flow**
   - Login to portal
   - Generate SSO token
   - Test login to XenForo

4. **Production Preparation**
   - Generate new shared secret
   - Update URLs for production domains
   - Enable SSL certificate verification
   - Set up automated token cleanup cron job

---

**Status**: ✅ All files created successfully  
**Database**: ✅ Migration executed  
**Ready**: ✅ Ready for XenForo addon installation
