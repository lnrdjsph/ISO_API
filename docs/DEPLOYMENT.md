# Deployment

Environment setup, configuration, and deployment procedures.

## Local Development Setup

### Prerequisites

- **PHP 8.0+** (with OCI8 extension for Oracle)
- **MySQL 5.7+**
- **Composer** (PHP dependency manager)
- **Node.js 16+** (for Vite/frontend build)
- **NPM** or **Yarn** (frontend package manager)

### Installation

```bash
# Clone repository
git clone <repo-url> iso_ordering_api
cd iso_ordering_api

# Install PHP dependencies
composer install

# Install frontend dependencies
npm install

# Copy environment template
cp .env.example .env

# Generate app key
php artisan key:generate

# Create database and run migrations
php artisan migrate

# (Optional) Seed database with sample data
php artisan db:seed

# Build frontend assets
npm run build

# Start dev server
php artisan serve
# Or use XAMPP Apache: place in htdocs/ and access http://localhost/iso_ordering_api

# In another terminal, start Vite dev server (optional, for hot reload)
npm run dev
```

**Access:** http://localhost:8000 (artisan serve) or http://localhost/iso_ordering_api (Apache)

---

## Environment Variables (`.env`)

### Database

```env
# MySQL (Primary)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=iso_ordering_api
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
```

### Oracle Connections

```env
# Oracle RMS (Read-Only)
ORACLE_RMS_HOST=rms.metroretail.ph
ORACLE_RMS_PORT=1521
ORACLE_RMS_SID=ORCL
ORACLE_RMS_USER=rms_readonly
ORACLE_RMS_PASS=<secure_password>

# Oracle MBC (Read-Only - iCard)
ORACLE_MBC_HOST=mbc.metroretail.ph
ORACLE_MBC_PORT=1521
ORACLE_MBC_SID=ORCL
ORACLE_MBC_USER=mbc_readonly
ORACLE_MBC_PASS=<secure_password>

# Oracle WMS (Read-Write - Allocations)
ORACLE_WMS_HOST=wms.metroretail.ph
ORACLE_WMS_PORT=1521
ORACLE_WMS_SID=ORCL
ORACLE_WMS_USER=wms_app
ORACLE_WMS_PASS=<secure_password>
```

### Application

```env
# Core
APP_NAME="MarengEms"
APP_ENV=production  # or "local" for development
APP_KEY=<generated-by-php-artisan-key:generate>
APP_DEBUG=false  # MUST be false in production
APP_URL=https://ordering-api.metroretail.ph  # or http://localhost for dev

# Session
SESSION_DRIVER=database
SESSION_LIFETIME=120  # minutes
CACHE_DRIVER=file  # or "redis" if available

# Queue
QUEUE_CONNECTION=database  # or "redis" for production
```

### Authentication & Security

```env
# Fortify (Login/Register)
FORTIFY_GUARD=web
FORTIFY_PASSWORD_RESET_TIMEOUT=60

# Sanctum (API Tokens)
SANCTUM_STATEFUL_DOMAINS=localhost:3000,ordering-api.metroretail.ph

# API Token for WooCommerce Webhook
ATOM_API_TOKEN=<strong-random-token-64-chars>
# Generate: php -r 'echo bin2hex(random_bytes(32));'
```

### Integrations

#### jPOS (ISO 8583 Card Payments)

```env
JPOS_HOST=jpos.metroretail.ph
JPOS_PORT=9999
JPOS_TIMEOUT=30  # seconds
```

#### SFTP (RMS Sync Upload)

```env
SFTP_HOST=woocommerce.metroretail.ph
SFTP_PORT=22
SFTP_USER=rms_sync
SFTP_PASS=<secure_password>
SFTP_PATH=/var/www/woocommerce/imports  # Remote directory
```

**SECURITY CONCERN:** Do NOT commit credentials to git. Use `.env.production` or vault system in production.

#### Email (Notifications)

```env
MAIL_DRIVER=smtp
MAIL_HOST=smtp.gmail.com  # or company mail server
MAIL_PORT=587
MAIL_USERNAME=<sender-email@metroretail.ph>
MAIL_PASSWORD=<app-password-or-token>
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@metroretail.ph
MAIL_FROM_NAME="MarengEms"
```

#### OTP Service (if external)

```env
OTP_PROVIDER=twilio  # or "custom", "nexmo", etc.
OTP_API_KEY=<api-key>
OTP_API_SECRET=<api-secret>
OTP_TIMEOUT=180  # seconds (3 minutes)
```

### Logging

```env
LOG_CHANNEL=stack
LOG_LEVEL=debug  # Use "error" in production
LOG_DAILY_PATH=storage/logs

# Separate channel for orders (verbose logging)
LOG_ORDERS_CHANNEL=orders
```

---

## Database Migrations & Schema

### First-Time Setup

```bash
# Run all pending migrations
php artisan migrate

# Seed optional sample data
php artisan db:seed

# Verify schema
php artisan tinker
>>> Schema::getColumnListing('orders')
```

### Adding New Migrations

```bash
# Create a new migration
php artisan make:migration create_table_name_table

# Or with quick stub
php artisan make:migration create_table_name_table --create=table_name

# Edit the migration file and run
php artisan migrate
```

### Rolling Back

```bash
php artisan migrate:rollback              # Undo last batch
php artisan migrate:rollback --step=3     # Undo last 3 batches
php artisan migrate:reset                 # Undo all (dev only)
php artisan migrate:refresh               # Reset and re-run (dev only)
```

---

## Frontend Build

### Development

```bash
npm run dev
# Starts Vite dev server with HMR
# Access via: http://localhost:5173 (Vite proxy)
# Or http://localhost:8000 with HMR enabled
```

### Production

```bash
npm run build
# Generates minified, hashed assets in public/
# Commits hash to filename (e.g., app.a1b2c3d4.js)
```

### CSS & Tailwind

All CSS is processed by Tailwind via `resources/css/app.css`.

```css
/* resources/css/app.css */
@tailwind base;
@tailwind components;
@tailwind utilities;

/* Custom components can go here */
```

### JavaScript

Main entry in `resources/js/app.js`. Uses ES6 modules:

```javascript
// resources/js/app.js
import { initOrderForm } from './modules/order-form.js';

document.addEventListener('DOMContentLoaded', () => {
    initOrderForm('#order-form');
});
```

---

## Deployment Checklist

### Pre-Deployment

- [ ] Run tests: `php artisan test`
- [ ] Code review: `git diff origin/main...HEAD`
- [ ] Build frontend: `npm run build` (verify no errors)
- [ ] Check `.env.production` for correct credentials
- [ ] Verify all migrations are tested locally
- [ ] No hardcoded secrets in code (search for password, token, key)

### To Production

#### Option 1: Manual Deployment (VPS/Dedicated Server)

```bash
# On production server
cd /var/www/iso_ordering_api

# Pull latest code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# Run migrations
php artisan migrate --force

# Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue workers (if applicable)
php artisan queue:restart

# Restart web server
systemctl restart php-fpm.service
systemctl restart nginx  # or apache2
```

#### Option 2: Docker Deployment

Create `Dockerfile`:

```dockerfile
FROM php:8.0-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libmcrypt-dev \
    git \
    zip \
    && docker-php-ext-install pdo pdo_mysql

# Install OCI8 (for Oracle)
RUN docker-php-ext-install oci8

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

RUN composer install --no-dev
RUN npm install && npm run build

CMD ["php-fpm"]
```

Docker Compose:

```yaml
version: '3.8'
services:
  web:
    image: iso-ordering-api:latest
    ports:
      - "8000:8000"
    environment:
      - DB_HOST=mysql
      - APP_ENV=production
    depends_on:
      - mysql
  
  mysql:
    image: mysql:5.7
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: iso_ordering_api
    volumes:
      - db_data:/var/lib/mysql

volumes:
  db_data:
```

#### Option 3: Platform-as-a-Service (PaaS)

**Heroku, Netlify, Laravel Forge, etc.:**
- Connect git repo
- Set environment variables in platform UI
- Platform auto-builds and deploys on push

---

## Secrets Management

### Local Development (`.env`)

Use `.env.example` as template. Never commit `.env`:

```bash
# .gitignore should contain:
.env
.env.local
.env.*.local
```

### Production (Vault/Secrets Manager)

**Use one of:**
1. **AWS Secrets Manager** — For AWS deployments
2. **HashiCorp Vault** — Self-hosted or cloud
3. **Laravel Vapor** — Built-in secrets management
4. **Platform-specific:** Heroku Config Vars, Vercel Env, etc.

**Never:**
- Hardcode secrets in code
- Commit `.env` files
- Share production credentials via email/Slack
- Use weak tokens (use `openssl rand -hex 32` to generate strong ones)

---

## Monitoring & Logging

### Application Logs

```bash
# Real-time log viewing
tail -f storage/logs/laravel.log

# Errors only
tail -f storage/logs/laravel.log | grep ERROR

# Last 100 lines
tail -100 storage/logs/laravel.log
```

### Order-Specific Logging

Separate channel for verbose order tracking:

```env
LOG_ORDERS_CHANNEL=orders  # Logs to storage/logs/orders.log
```

### Queue Job Monitoring

```bash
# View failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Monitor queue status
watch php artisan queue:monitor
```

### Database Query Logging (Development Only)

```php
// In a service or controller
DB::enableQueryLog();
// ... run queries ...
dd(DB::getQueryLog());  // Dump all executed queries
```

---

## Performance Optimization

### Caching

Enable all caches in production:

```bash
php artisan config:cache    # Cache config files
php artisan route:cache     # Cache routes
php artisan view:cache      # Cache Blade views
php artisan event:cache     # Cache listeners
```

Clear caches after deployment:

```bash
php artisan cache:clear
php artisan config:clear  # (then re-cache)
```

### Database

```sql
-- Analyze tables for query optimization
ANALYZE TABLE orders;
ANALYZE TABLE order_items;
ANALYZE TABLE product_wms_allocations;

-- Check index usage
SELECT * FROM performance_schema.table_io_waits_summary_by_index_usage;
```

### File Upload Storage

Configure S3 or CDN for large files instead of local filesystem:

```env
FILESYSTEM_DISK=s3

AWS_ACCESS_KEY_ID=<key>
AWS_SECRET_ACCESS_KEY=<secret>
AWS_DEFAULT_REGION=ap-southeast-1
AWS_BUCKET=iso-ordering-api-uploads
```

---

## Troubleshooting

### 500 Internal Server Error

```bash
# Check logs
tail -100 storage/logs/laravel.log

# Verify permissions
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/

# Verify database connection
php artisan tinker
>>> DB::connection('mysql')->getPdo()
```

### Queue Jobs Not Processing

```bash
# Start queue worker
php artisan queue:work

# Or in background
nohup php artisan queue:work &

# Monitor
php artisan queue:monitor
```

### Oracle Connection Issues

```bash
# Test Oracle connection
php artisan tinker
>>> DB::connection('oracle_rms')->select('SELECT 1 FROM DUAL')
```

### SFTP Upload Failures

```bash
# Test SFTP credentials
php artisan tinker
>>> $sftp = new \phpseclib3\Net\SFTP(config('services.sftp.host'));
>>> $sftp->login(config('services.sftp.user'), config('services.sftp.pass'))
>>> $sftp->put('/remote/test.txt', 'Hello')
```

---

## Backup & Recovery

### Database Backup

```bash
# Backup MySQL
mysqldump -u root -p iso_ordering_api > backup_$(date +%Y%m%d_%H%M%S).sql

# Restore from backup
mysql -u root -p iso_ordering_api < backup_20260609_120000.sql
```

### Application Files

```bash
# Backup entire app (excluding node_modules, storage, vendor)
tar -czf iso_ordering_api_backup_$(date +%Y%m%d).tar.gz \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='storage/logs' \
    --exclude='.env' \
    .
```

---

For environment variables in detail, see this document's "Environment Variables" section.  
For architectural considerations, see [ARCHITECTURE.md](ARCHITECTURE.md).
