# Production Fix Guide - "never can only be used as a standalone type"

## IMMEDIATE ACTION - For dev.kutoot.com

### Step 1: SSH into your production server
```bash
ssh user@dev.kutoot.com
cd /path/to/kutoot
```

### Step 2: Clear all caches immediately
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear  
php artisan route:clear
php artisan optimize:clear
```

### Step 3: Verify PHP syntax of all files
```bash
# Check for parse errors
find app routes config -name "*.php" -type f -exec php -l {} \; | grep -i "parse error\|error\|never"

# Or use this script
php -r '
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("app"));
$errors = [];
foreach ($files as $file) {
    if ($file->getExtension() === "php") {
        exec("php -l " . escapeshellarg($file->getPathname()) . " 2>&1", $out, $ret);
        if ($ret !== 0) {
            $errors[] = $file->getPathname();
        }
    }
}
if ($errors) {
    echo "❌ Parse errors found:\n";
    foreach ($errors as $e) echo "  $e\n";
} else {
    echo "✓ All files parse successfully\n";
}
'
```

### Step 4: Check Composer status
```bash
composer diagnose
composer validate
```

### Step 5: If still failing, try:
```bash
# Regenerate optimized class loader
composer dump-autoload -o

# Clear framework caches
php artisan cache:forget bootstrap

# Rebuild the autoloader
php artisan ide-helper:generate  # if installed

# Re-run migrations if needed
php artisan migrate --force
```

### Step 6: Search production code for the issue
```bash
# Create a search script on the server
php << 'EOF'
<?php
$patterns = [
    '/:.*\|.*never/',     // Union with never
    '/:\s+.+\|never/',    // Something|never
    '/never\s*\|/',       // never|something
];

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator('app'),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($files as $file) {
    if ($file->getExtension() !== 'php') continue;
    
    $content = file_get_contents($file);
    $lines = explode("\n", $content);
    
    foreach ($lines as $num => $line) {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $line) && !preg_match('/^\s*(\/\/|\/\*|#|@)/', trim($line))) {
                echo "Found in {$file->getPathname()}:{" . ($num+1) . "}\n";
                echo "  $line\n";
            }
        }
    }
}
EOF
```

## Potential Sources

The error might be coming from:

1. **Filament Package Mismatch**
   - Your local uses a different version than production
   - Fix: `composer update filament/filament`

2. **Custom Enums**
   - Check any enum classes with type hints
   - Look for: `public function method(): string|never`

3. **Third-party Packages**
   - A package in vendor/ has the issue
   - Fix: Update the package or pin to compatible version
   
4. **Database Migrations**
   - A recent migration might have generated problematic code
   - Fix: Rollback and regenerate: `php artisan migrate:rollback`

5. **Configuration Caching**
   - Cached config has the bad code
   - Fix: Already done in Step 2 above

## Prevention for Future Releases

Add to `composer.json`:
```json
{
    "require": {
        "php": "^8.5"
    },
    "scripts": {
        "lint": [
            "find app -name '*.php' -exec php -l {} \\;"
        ],
        "pre-commit": [
            "@lint"
        ]
    }
}
```

Then run validation before each deploy:
```bash
composer lint
```

## Additional Resources

- [PHP 8.4 Never Type Documentation](https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.never-type)
- [Upgrade Guide](https://www.php.net/migration84.php)

## Still Having Issues?

1. Check your `php.ini` for any type checking settings
2. Verify opcache isn't caching old code: `php -r 'opcache_reset();'`
3. Restart PHP-FPM: `sudo systemctl restart php-fpm` (or your version)
4. Check error logs: `tail -f storage/logs/laravel-*.log`
