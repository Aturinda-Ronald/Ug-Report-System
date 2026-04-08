# VirtualHost Configuration Fix for Uganda Results System

## ❌ Current Problem
- **ERR_TOO_MANY_REDIRECTS** when accessing `172.16.21.100:8082`
- VirtualHost only accepts `127.0.0.1`, not external IPs

## ✅ Solution: Update Your XAMPP VirtualHost Configuration

### 1. Edit your Apache configuration file (httpd-vhosts.conf):

**Replace your current VirtualHost with this:**

```apache
# ================== BEGIN: reports_system redirect on 8082 ==================
<VirtualHost *:8082>
    ServerName localhost
    ServerAlias *
    DocumentRoot "C:/xampp/htdocs/reports_system/public"

    <Directory "C:/xampp/htdocs/reports_system/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    <Directory "C:/xampp/htdocs/reports_system">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    DirectoryIndex index.php index.html index.htm

    ErrorLog "logs/reports-8082-error.log"
    CustomLog "logs/reports-8082-access.log" combined
</VirtualHost>
# ================== END: reports_system redirect on 8082 ==================

Listen 8082
```

### 2. Key Changes Made:
- ✅ **DocumentRoot** now points to `/public` directory
- ✅ **ServerAlias ***: Accepts any IP address, not just 127.0.0.1
- ✅ **Directory permissions**: Set for both public and parent directories

### 3. Code Changes Made:
- ✅ **base_url()** function now detects VirtualHost (port 8082)
- ✅ **asset_url()** function uses same detection logic
- ✅ **Root index.php** redirects to public/index.php for VirtualHost

## 🧪 Testing Steps

### After updating VirtualHost configuration:

1. **Restart Apache** in XAMPP Control Panel
2. **Test localhost access:**
   ```
   http://localhost/reports_system/public/
   ```
3. **Test IP:port access:**
   ```
   http://172.16.21.100:8082/
   ```
4. **Test URL generation:**
   ```
   http://172.16.21.100:8082/test-url-fix.php
   ```

## 🎯 Expected Results

**Localhost (normal):**
- URLs: `localhost/reports_system/public/auth/login-admin.php`
- Assets: `localhost/reports_system/assets/css/style.css`

**VirtualHost (port 8082):**
- URLs: `172.16.21.100:8082/auth/login-admin.php`
- Assets: `172.16.21.100:8082/assets/css/style.css`

## 🔧 Troubleshooting

If you still get redirect loops:
1. Clear browser cache and cookies
2. Check Apache error logs: `logs/reports-8082-error.log`
3. Verify the VirtualHost configuration is correctly applied
4. Test with different browsers or incognito mode

## 💡 Alternative Simple Solution

If VirtualHost still causes issues, you can serve directly from public:
```apache
<VirtualHost *:8082>
    ServerName *
    DocumentRoot "C:/xampp/htdocs/reports_system/public"
    DirectoryIndex index.php
</VirtualHost>
```
