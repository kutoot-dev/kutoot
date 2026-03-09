# Fix for "never can only be used as a standalone type" Error

## Summary

You encountered a PHP 8.4+ compatibility error: **"never can only be used as a standalone type"**

### Root Cause
In PHP 8.4+, the `never` return type cannot be combined with other types in union types (e.g., `string|never`, `int|never`).

### Local Status ✓ 
Your local kutoot codebase is **clean** - no problematic `never` type usage found in:
- `app/` directory
- All Models
- All Controllers  
- All Resources
- All Services

### Production Status
The error occurs on `dev.kutoot.com` running PHP 8.4.18. This suggests:
1. A version mismatch between local (PHP 8.5.1) and production (PHP 8.4.18)
2. Possibly a cached config or autoloader issue
3. Or a package dependency that needs updating

## What to Do Now

### Option 1: Immediate Fix (Most Likely Solution)
Run these commands on production to clear outdated caches:

```bash
ssh user@dev.kutoot.com
cd /var/www/kutoot  # adjust path as needed

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
php artisan optimize:clear

# Rebuild autoloader
composer dump-autoload -o

# Verify no syntax errors
find app -name "*.php" -exec php -l {} \;
```

### Option 2: Full Deploy
```bash
# Push your current clean code
git push origin main

# On production
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan cache:clear
```

### Option 3: Specific File Fix (If you find the issue)

**Find the problematic file:**
1. Look in production logs: `tail -f storage/logs/laravel-*.log`
2. Find lines with pattern: `): type|never` or `|never|`

**Fix the file:**
```php
// ❌ WRONG
public function handle(): string|never {
    if (error) throw new Exception();
    return "ok";
}

// ✅ CORRECT  
public function handle(): string {
    if (error) throw new Exception();
    return "ok";
}
```

## Files Created for You

1. **NEVER_TYPE_FIX_GUIDE.md** - Detailed explanation and fixing patterns
2. **PRODUCTION_FIX_GUIDE.md** - Step-by-step production server recovery

## Prevention Going Forward

### Add to development workflow:
```bash
# In your pre-commit hook or CI/CD
php -l app/**/*.php routes/**/*.php config/**/*.php

# Or add to composer.json scripts:
"lint": "find app -name '*.php' -exec php -l {} \\;"
```

### Update composer.json to specify PHP version:
```json
{
    "require": {
        "php": "^8.5"
    },
    "engines": {
        "php": "8.5.*"
    }
}
```

## Next Steps

1. **Try immediate fix first** (clear caches)
2. If still failing, check production logs for exact file/line
3. If needed, pull latest code and redeploy
4. Implement prevention measures in your CI/CD

## Still Having Issues?

Check:
- [ ] `php -v` confirms PHP version on production
- [ ] No opcache issues: verify PHP-FPM is restarted
- [ ] Logs show no other parse errors
- [ ] Project files actually deployed (git pull worked)
- [ ] Relative paths are correct in your deployment

## Support

If the error persists after these steps:
1. Share the exact error line from `storage/logs/laravel.log`  
2. Confirm your PHP version: `php -v`
3. Check if any packages recently updated that added `never` types
