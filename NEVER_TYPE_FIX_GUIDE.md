# PHP 8.4+ - "never can only be used as a standalone type" Fix Guide

## Understanding the Error

In PHP 8.4+, the `never` return type has stricter validation rules:

### ❌ INVALID - These will cause the error:
```php
// Union with never
function doSomething(): string|never { }
function throwError(): int|never { }
function process(): array|string|never { }
function handle(): bool|never { }

// Intersection with never  
function mixed(): (int&never) { }

// In property types
class MyClass {
    private string|never $property;
    public float|never $value;
}
```

### ✅ VALID - Use like this:
```php
// Standalone never - function never returns
function throwError(): never {
    throw new Exception("Always throws");
}

function exitApp(): never {
    exit(1);
}

// If function CAN return a value, don't use never
function doSomething(): string {
    // ... code
    return "value";
}

// In property types - never is not allowed
class MyClass {
    private string $property;  // Regular type
    public float $value;       // No never
}
```

## Finding the Problem

### Method 1: Search in your codebase
```bash
# Look for problematic patterns
grep -r ":\s\+[^:]*|.*never\|never\s*|" app/ --include="*.php"
grep -r ":\s\+[^:]*|.*never\|never\s*|" routes/ --include="*.php"
grep -r ":\s\+[^:]*|.*never\|never\s*|" config/ --include="*.php"
```

### Method 2: Run PHP linter
```bash
php -l app/**/*.php 2>&1 | grep -i "never"
```

### Method 3: Check Recent Changes
- Review recent migrations
- Check git log for type hint changes
- Review Composer updates (may have introduced incompatible packages)

## Quick Fixes

### Fix Pattern 1: Remove Union with never
**Before:**
```php
public function process(): string|never {
    if ($error) {
        throw new Exception("Error");
    }
    return "success";
}
```

**After:**
```php
public function process(): string {
    if ($error) {
        throw new Exception("Error");
    }
    return "success";
}
```

### Fix Pattern 2: Handle Always-Throwing Functions
**Before:**
```php
public function abort(): int|never {
    exit(1);
}
```

**After:**
```php
public function abort(): never {
    exit(1);
}
```

## Prevention Checklist

- [ ] Update composer.json to require `php: ^8.5` for better type compatibility
- [ ] Use static analysis tools like PHPStan with PHP 8.4+ level
- [ ] Set up CI/CD linting for all PHP files
- [ ] Run `php -l` on all PHP files before commit
- [ ] Enable strict_types in all files: `declare(strict_types=1);`
- [ ] Use PHP_CodeSniffer with modern PHP ruleset

## CI/CD Script to Catch This

Add to your build pipeline:

```bash
#!/bin/bash
# validate-php-syntax.sh

ERROR=0
for file in $(find . -name "*.php" -path "./app/*" -o -path "./routes/*" -o -path "./config/*"); do
    if ! php -l "$file" > /dev/null 2>&1; then
        php -l "$file"
        ERROR=1
    fi
    
    if grep -E ":\s*[^:]*(never\s*\||never\s*&|\|.*never)" "$file" > /dev/null; then
        echo "Warning: Possibly invalid 'never' type in $file"
        grep -n ":\s*[^:]*(never\s*\||never\s*&|\|.*never)" "$file"
        ERROR=1
    fi
done

exit $ERROR
```

## For Production Server (dev.kutoot.com)

1. SSH into your server
2. Check PHP version: `php -v`
3. Look for the file mentioned in error (app/Http/Controllers/StampController.php)
4. Apply the fixes shown above
5. Clear caches:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   php artisan route:clear
   ```
6. Run `php artisan migrate` if needed
7. Test the route that was failing
