# Environment Configuration

Kutoot uses separate environment files for each deployment target. The `.env` file is the active
configuration - swap it by copying the appropriate source file.

## Available Environments

| File              | Purpose                  | Git Tracked | Secrets |
|-------------------|--------------------------|-------------|---------|
| `.env.example`    | Template for new setups  | Yes         | None    |
| `.env.testing`    | Automated test runs      | Yes         | None    |
| `.env.local`      | Local development (Herd) | No          | Yes     |
| `.env.production` | Production server        | No          | Yes     |

## Quick Start

### Switch to Local Development

```bash
cp .env.local .env
php artisan config:clear
```

### Switch to Production

```bash
cp .env.production .env
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan icons:cache
```

### Run Tests

No swap needed - Laravel automatically uses `.env.testing`:

```bash
php artisan test --compact
```

## Key Differences Between Environments

| Setting            | Local         | Production    | Testing   |
|--------------------|---------------|---------------|-----------|
| FILESYSTEM_DISK    | public        | s3            | public    |
| APP_ENV            | local         | production    | testing   |
| APP_DEBUG          | true          | false         | true      |
| CACHE_STORE        | file          | redis         | array     |
| SESSION_DRIVER     | file          | redis         | array     |
| QUEUE_CONNECTION   | sync          | redis         | sync      |
| MAIL_MAILER        | log           | smtp          | array     |
| SMS_DRIVER         | log           | way2mint      | log       |
| LOG_LEVEL          | debug         | warning       | debug     |
| BCRYPT_ROUNDS      | 4             | 12            | default   |
| SESSION_ENCRYPT    | false         | true          | n/a       |

## Production Deployment Commands

Run these after deploying new code:

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan optimize        # caches config, routes, views, events
php artisan icons:cache
php artisan migrate --force
```

To clear all caches:

```bash
php artisan optimize:clear
```

---

## Laravel Cloud Setup

Laravel Cloud does **NOT** use `.env` files on disk. Environment variables are managed
per-environment via the Cloud dashboard. Use `.env.production` as a reference.

### Step 1: Create Your App and Environments

1. Go to [cloud.laravel.com](https://cloud.laravel.com)
2. Create your application and link the GitHub repository
3. Create environments: `production`, `staging` (optional)

### Step 2: Attach Resources

In your environment settings, attach:

| Resource         | Purpose                                | Auto-Injected Variables         |
|------------------|----------------------------------------|---------------------------------|
| Database         | MySQL for application data             | DB_* variables                  |
| KV Store         | Redis for cache, sessions, queues      | REDIS_*, CACHE_STORE, etc.      |
| Object Storage   | S3-compatible storage (if needed)      | AWS_*, FILESYSTEM_DISK          |

> When you attach a Database and KV Store, Laravel Cloud **auto-injects** DB_*, REDIS_*,
> CACHE_STORE, SESSION_DRIVER, and QUEUE_CONNECTION - you do NOT need to set these manually.

### Step 3: Set Custom Environment Variables

In **Dashboard > Environment > Settings > Environment Variables**, add these
variables that Laravel Cloud does NOT auto-inject:

```
APP_NAME=Kutoot
APP_TIMEZONE="Asia/Kolkata"
APP_CURRENCY=INR
APP_DEBUG=false
LOG_LEVEL=warning
LOG_STACK=daily
BCRYPT_ROUNDS=12
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
PLATFORM_FEE=10
GST_RATE=18
PLATFORM_FEE_TYPE=fixed
PLAN_TAX_TYPE=exclusive
PAYMENT_DEFAULT_GATEWAY=razorpay
RAZORPAY_KEY_ID=rzp_live_XXXXX
RAZORPAY_KEY_SECRET=your_live_secret
RAZORPAY_WEBHOOK_SECRET=your_webhook_secret
SMS_DRIVER=way2mint
WAY2MINT_BASE_URL=https://apibulksms.way2mint.com
WAY2MINT_USERNAME=kutoot
WAY2MINT_PASSWORD=your_production_password
WAY2MINT_SENDER_ID=KUTOOT
WAY2MINT_PE_ID=1701175557617315269
WAY2MINT_OTP_TEMPLATE_ID=1707175557997706011
WAY2MINT_PROVIDER_PE_ID=1702173216915572636
OTP_LENGTH=4
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=it@kutoot.com
MAIL_PASSWORD=your_production_mail_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="it@kutoot.com"
MAIL_FROM_NAME="Kutoot"
ACTIVITY_LOGGER_ENABLED=true
ACTIVITY_LOGGER_DB_CONNECTION=mysql
LOG_VIEWER_ENABLED=true
LOG_VIEWER_EMAILS=it@kutoot.com
NATIVEPHP_APP_ID=www.kutoot.com
NATIVEPHP_APP_VERSION="1.0.0"
NATIVEPHP_APP_VERSION_CODE="1"
```

> Custom variables override auto-injected ones. Redeploy after changes.

### Step 4: Configure Build Commands

In **Dashboard > Deployments > Build Commands**:

```
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan optimize
php artisan icons:cache
```

### Step 5: Configure Deploy Commands

```
php artisan migrate --force
```

> **Do NOT add** these to deploy commands (Cloud handles them automatically):
> - `php artisan queue:restart`
> - `php artisan horizon:terminate`
> - `php artisan optimize:clear`
> - `php artisan storage:link` (filesystem is ephemeral - use Object Storage)

### Step 6: Staging Environment (Optional)

Use **Replicate** to clone production:
1. Click **...** > **Replicate** on the production environment
2. Name it `staging`, select `develop` branch
3. Override these variables:
   - `APP_DEBUG=true`
   - `LOG_LEVEL=debug`
   - `RAZORPAY_KEY_ID=rzp_test_XXXXX` (test keys)
   - `SMS_DRIVER=log`
   - `MAIL_MAILER=log`

Cloud auto-adds a unique `CACHE_PREFIX` to avoid data conflicts with production.

### Important Laravel Cloud Notes

- **Filesystem is ephemeral** - files don't persist across deploys or replicas. Use Object Storage for uploads.
- **Use redis or database for cache/session** - file driver won't work across replicas.
- **php artisan optimize goes in Build commands**, NOT Deploy commands.
- **Octane**: Enable in environment compute settings if using Laravel Octane.
- **Preview Environments**: Available on Growth/Business plans - auto-creates environments per PR.

---

## Redis Setup (Production Ubuntu)

```bash
sudo apt update && sudo apt install redis-server -y
sudo systemctl enable redis-server
sudo systemctl start redis-server
redis-cli ping  # Should return PONG
```

## Redis Setup (Local Windows WSL)

```bash
sudo apt update && sudo apt install redis-server -y
sudo service redis-server start
redis-cli ping
```

If using Redis locally, update `.env.local`:

```
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```
