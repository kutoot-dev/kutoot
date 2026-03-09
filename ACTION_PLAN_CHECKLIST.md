# Action Plan Checklist - PHP 8.4 "never" Type Error

## Local Environment Status
- [x] **VERIFIED**: No problematic `never` types in kutoot source code
- [x] **VERIFIED**: All app files parse correctly  
- [x] **NOTE**: Local environment: PHP 8.5.1
- [x] **NOTE**: Production environment: PHP 8.4.18

## Immediate Actions (Do These First)

### Short-term Fix - Clear Caches
- [ ] SSH into dev.kutoot.com
- [ ] Navigate to project directory: `cd /var/www/kutoot`
- [ ] Run: `php artisan cache:clear`
- [ ] Run: `php artisan config:clear`
- [ ] Run: `php artisan view:clear`
- [ ] Run: `php artisan route:clear`  
- [ ] Run: `php artisan optimize:clear`
- [ ] Test the homepage: `https://dev.kutoot.com/` - does the error go away?

If error persists, continue to next section...

### If Cache Clear Didn't Work

- [ ] Composer dump autoload: `composer dump-autoload -o`
- [ ] Check for syntax errors: `find app -name "*.php" -exec php -l {} \;`
- [ ] Restart PHP-FPM: `sudo systemctl restart php8.4-fpm` (or your version)
- [ ] Test again
- [ ] Check logs: `tail -50 storage/logs/laravel-*.log`

### If Still Not Working

- [ ] Pull latest code: `git pull origin main`  
- [ ] Update dependencies: `composer install --no-dev`
- [ ] Run migrations: `php artisan migrate --force`
- [ ] Clear all caches again
- [ ] Test

## Finding the Exact Problem

If the error still occurs, find which file has the issue:

### Step 1: Check Recent Log
```bash
cat storage/logs/laravel-*.log | grep -A 5 "never can only"
```
Look for the file name indicated before this error.

### Step 2: Use This Search Command
```bash
php << 'EOF'
<?php
$search = ['/:\s*\w+\s*\|\s*never/', '/\|.*never\s*\|/', '/never\s*\|/'];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('app'));

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') continue;
    $content = file_get_contents((string)$file);
    $lines = explode("\n", $content);
    
    foreach ($lines as $num => $line) {
        foreach ($search as $pattern) {
            if (preg_match($pattern, $line) && !preg_match('/^\s*(\/\/|#)/', $line)) {
                echo "{$file}:{$num+1} -> {$line}\n";
            }
        }
    }
}
EOF
```

### Step 3: If You Find It
Look at the problematic line and fix following this pattern:

**BAD:**
```php
): string|never {
```

**GOOD:**
```php
): string {        // If function can return a value
```

OR:
```php
): never {          // If function ALWAYS throws/exits/loops
```

## Deployment Verification

After any fix:
- [ ] Test locally: `npm run dev` (frontend) and `php artisan serve` (backend)
- [ ] Deploy to staging first if available
- [ ] Deploy to production
- [ ] Test on production

## Long-Term Prevention

- [ ] Add pre-commit hook to check PHP syntax
- [ ] Add composer.json lint script:
  ```json
  "scripts": {
    "lint": "find app routes -name '*.php' | xargs php -l"
  }
  ```
- [ ] Run `composer lint` before every push
- [ ] Consider adding PHPStan for static analysis
- [ ] Set up CI/CD pipeline to catch syntax errors

## Documentation References

1. [NEVER_TYPE_FIX_GUIDE.md](./NEVER_TYPE_FIX_GUIDE.md) - Comprehensive fix guide
2. [PRODUCTION_FIX_GUIDE.md](./PRODUCTION_FIX_GUIDE.md) - Server-specific fixes
3. [PHP 8.4 Never Type](https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.never-type)

## Contact / Support

If you need help:
1. Check the error logs first
2. Run the cache clear commands
3. If error persists, note:
   - Exact error message from logs
   - Expected vs actual output
   - Steps already taken

---

**Last Updated**: March 9, 2026  
**Status**: Diagnostic Complete - Ready for Production Fix
