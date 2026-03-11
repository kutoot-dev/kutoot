# File Upload Fix – Deployment Checklist (main-latest)

S3 uploads configured for Spatie Media Library + Filament. Works with buckets that have ACLs disabled.

---

## Files Changed

| File | Change |
|------|--------|
| `config/filesystems.php` | Public disk uses S3 when FILESYSTEM_DRIVER/DISK=s3, ACL fix |
| `config/livewire.php` | Local disk for temp uploads (avoids S3 presigned URL ACL errors) |
| `routes/web.php` | `/storage/{path}` stream route for private S3 |

---

## .env Changes (apply on server)

**Add or update these lines:**

```env
# Both for redundancy (config checks both)
FILESYSTEM_DRIVER=s3
FILESYSTEM_DISK=s3

# Use 'public' so media URLs go through /storage stream route (private S3)
MEDIA_DISK=public

# Livewire temp uploads use local disk (avoids S3 presigned URL ACL errors)
LIVEWIRE_TEMP_UPLOAD_DISK=local

# Single global upload limit in MB (default 100). Used by Media Library, Filament, API.
MAX_UPLOAD_SIZE_MB=100
```

---

## Deploy Steps

```bash
cd /var/www/kutoot
git pull

# Ensure livewire-tmp exists for temp uploads
mkdir -p storage/app/livewire-tmp
chmod 775 storage/app/livewire-tmp

php artisan config:clear
php artisan config:cache
php artisan route:clear
php artisan route:cache
```

---

## Nginx

Ensure `/storage/` is handled by Laravel (not static files):

```nginx
location /storage/ {
    try_files $uri $uri/ /index.php?$query_string;
}
```

---

## IAM Permissions

```json
{
    "Effect": "Allow",
    "Action": ["s3:GetObject", "s3:PutObject", "s3:DeleteObject"],
    "Resource": "arn:aws:s3:::kutoot-backend/*"
}
```

---

## Verify

```bash
php artisan tinker
```

```php
\Illuminate\Support\Facades\Storage::disk('public')->put('test.txt', 'hello');
\Illuminate\Support\Facades\Storage::disk('public')->exists('test.txt');
```
