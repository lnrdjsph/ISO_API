# Troubleshooting

Common issues, debug tips, and solutions.

## Database

### MySQL Connection Fails

**Error:** `SQLSTATE[HY000] [2002] No such file or directory`

**Causes & Solutions:**
- MySQL service not running
  ```bash
  sudo systemctl start mysql
  # or on macOS
  brew services start mysql
  ```
- Wrong `.env` credentials
  ```bash
  # Test connection
  mysql -h 127.0.0.1 -u root -p iso_ordering_api -e "SELECT 1;"
  ```
- Socket mismatch (dev only)
  ```php
  // config/database.php
  'mysql' => [
      'unix_socket' => env('DB_SOCKET', '/var/run/mysqld/mysqld.sock'),
      // or on macOS: '/tmp/mysql.sock'
  ]
  ```

### Migration Fails

**Error:** `Illuminate\Database\QueryException: SQLSTATE[HY000]: General error: 1030`

**Causes & Solutions:**
- Disk space full → Check: `df -h`
- Corrupted table → Repair:
  ```sql
  REPAIR TABLE orders;
  ```
- Foreign key constraint → Disable temporarily (dev only):
  ```php
  // In migration
  Schema::disableForeignKeyConstraints();
  // ... your schema changes
  Schema::enableForeignKeyConstraints();
  ```

### Oracle Connection Fails

**Error:** `OCI8 extension not loaded`

**Solution:** Install OCI8 extension:
```bash
# Ubuntu/Debian
sudo apt-get install php-dev
sudo pecl install oci8
echo "extension=oci8.so" | sudo tee -a /etc/php/8.0/fpm/php.ini

# Restart PHP-FPM
sudo systemctl restart php8.0-fpm
```

**Error:** `ORA-12154: TNS:could not resolve the connect identifier specified`

**Causes & Solutions:**
- Wrong host/port in `.env`
  ```env
  ORACLE_RMS_HOST=rms.metroretail.ph
  ORACLE_RMS_PORT=1521
  ORACLE_RMS_SID=ORCL
  ```
- Firewall blocking connection → Test:
  ```bash
  telnet rms.metroretail.ph 1521
  # Should connect; Ctrl+] to exit
  ```
- Oracle listener not running → Contact DBA

---

## Authentication & Authorization

### "Unauthorized" / 401 Error

**Web Routes (Session-Based):**

```bash
# Clear session cache
php artisan cache:clear

# Check session configuration
php artisan tinker
>>> config('session.driver')  # Should be 'database' or 'file'
>>> Cache::get('PHPSESSID')  # Check if session exists
```

**API Routes (Token-Based):**

```bash
# Verify token in request header
Authorization: Bearer <token>

# Generate new token if needed
php artisan tinker
>>> $user = User::find(1);
>>> $user->createToken('API Token')->plainTextToken;
```

### "Access Denied" / 403 Error

**Cause:** User role/location does not match resource

**Debug:**
```php
// In tinker or controller
>>> auth()->user()->role        # Check role
>>> auth()->user()->assigned_store  # Check assigned location
>>> auth()->user()->can('view', $order)  # Check policy
```

### User Not Logging In

**Cause:** Wrong credentials, email not verified, or user inactive

**Debug:**
```bash
php artisan tinker
>>> $user = User::where('email', 'user@example.com')->first();
>>> $user->is_active  # Check if active
>>> Hash::check('password', $user->password)  # Verify password
```

---

## Orders & Data Entry

### WooCommerce Webhook Not Creating Orders

**Error:** `409 Conflict - Duplicate order`

**Solution:** Clear deduplication cache
```bash
php artisan cache:clear
# Check: does SOF ID already exist in DB?
php artisan tinker
>>> Order::where('sof_id', 'ATOM12345-20260609')->first();
```

**Error:** `400 Bad Request - Stock unavailable`

**Solution:** Check WMS allocation
```php
php artisan tinker
>>> DB::table('product_wms_allocations')
    ->where('sku', 'SKU001')
    ->where('warehouse_code', 'WH001')
    ->first();
```

### Order Not Appearing in Approver Dashboard

**Cause:** Approver user not assigned to order's region

**Solution:** Verify approver routing
```php
php artisan tinker
>>> $order = Order::find(999);
>>> $order->requesting_store  # e.g., 'LZ'
>>> $store = SettingsStore::where('store_code', 'LZ')->first();
>>> $store->region_key  # e.g., 'MANILA'
>>> $approver_id = DB::table('settings_region_emails')
    ->where('region_key', 'MANILA')
    ->where('email', '__approver__')
    ->value('label');
>>> User::find($approver_id)->name;  # Should be approver's name
```

### Product Import Fails

**Error:** `Integrity constraint violation - Duplicate entry`

**Cause:** SKU already exists in `products_{store_code}` table

**Solution:**
```bash
php artisan tinker
>>> DB::table('products_lz')->where('sku', 'SKU001')->delete();  # Remove duplicate
# Then re-import
```

### Freebie Calculation Wrong

**Debug:**
```php
php artisan tinker
>>> $item = OrderItem::find(1);
>>> $item->quantity  # 120 pieces
>>> $item->case_pack_quantity  # 12
>>> $item->freebies_per_case  # 1
>>> ($item->quantity / $item->case_pack_quantity) * $item->freebies_per_case  # Should be 10
```

---

## Payments (ISO 8583)

### Card Transaction Fails

**Error:** `Connection timeout to jPOS host`

**Causes & Solutions:**
- jPOS service down → Contact payment provider
- Wrong host/port in `.env`
  ```env
  JPOS_HOST=jpos.metroretail.ph
  JPOS_PORT=9999
  ```
- Firewall blocking → Test:
  ```bash
  telnet jpos.metroretail.ph 9999
  ```

**Error:** `Field 39 (Response Code) = 05 (Do not honor)`

**Causes:**
- Card declined
- Insufficient funds
- Card expired
- Transaction limit exceeded

**Solution:** Check with payment provider / cardholder

---

## RMS Synchronization

### Sync Job Stuck / Not Running

**Debug:**
```bash
# Check queue status
php artisan queue:monitor

# View failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry {job_id}
```

### SFTP Upload Fails

**Error:** `phpseclib3\Net\SFTP: Authentication failed`

**Causes & Solutions:**
- Wrong credentials in `.env`
  ```env
  SFTP_USER=rms_sync
  SFTP_PASS=<correct_password>
  ```
- Test credentials manually:
  ```bash
  sftp -o StrictHostKeyChecking=no rms_sync@woocommerce.metroretail.ph
  # Enter password
  ```
- SSH key-based auth (recommended):
  ```env
  SFTP_KEY=/home/app/.ssh/id_rsa
  SFTP_PASS=<passphrase-if-encrypted>
  ```

**Error:** `Permission denied when writing to remote path`

**Solution:** Check SFTP user permissions
```bash
sftp> ls -la /var/www/woocommerce/imports
# Must be writable by SFTP_USER
```

### CSV Generated But Not Uploaded

**Debug:**
```bash
# Check local backup
ls -la storage/app/sync/  # Should have itemlist.csv, stores.csv

# Check logs
tail -50 storage/logs/laravel.log | grep -i sftp
```

---

## Performance & Timeouts

### Slow Queries / Page Load > 5s

**Debug:** Enable query logging
```php
// In controller or middleware
DB::enableQueryLog();
// ... execute request ...
Log::debug('Queries:', DB::getQueryLog());
```

**Common causes:**
- Missing indexes → Check:
  ```bash
  php artisan tinker
  >>> Schema::getIndexes('orders')
  ```
- N+1 query problem → Use eager loading:
  ```php
  // Bad
  $orders = Order::all();
  foreach ($orders as $o) { echo $o->approver->name; }
  
  // Good
  $orders = Order::with('approver')->get();
  ```
- Large result set → Add pagination:
  ```php
  Order::paginate(15);  // Default: paginate results
  ```

### 504 Gateway Timeout

**Causes:**
- Long-running sync job → Move to queue
- Slow RMS query → Add timeout or cache
- Memory limit exceeded → Check:
  ```bash
  php -r 'echo ini_get("memory_limit");'  # Increase if < 512M
  ```

**Solution:**
```php
// Set_time_limit for specific operations
set_time_limit(300);  // 5 minutes
```

---

## Frontend & Assets

### CSS Not Loading / Styles Broken

**Error:** `404 Not Found` for `public/css/app.css` or JS file

**Solution:** Rebuild assets
```bash
npm run build
# Verify files exist in public/
ls -la public/js/
ls -la public/css/
```

### Vite HMR Not Working (Dev Mode)

**Error:** `WebSocket connection failed`

**Causes & Solutions:**
- Vite dev server not running
  ```bash
  npm run dev  # In separate terminal
  ```
- Wrong host/port in `vite.config.js`
  ```javascript
  export default defineConfig({
      server: {
          hmr: {
              host: 'localhost',
              port: 5173,
          }
      }
  })
  ```

### JavaScript Console Errors

**Error:** `Uncaught ReferenceError: module is not defined`

**Cause:** Missing Vite polyfill or incorrect import

**Solution:** Wrap in module check
```javascript
if (typeof window !== 'undefined') {
    // Browser-specific code
}
```

---

## Environment & Config

### App Key Missing

**Error:** `RuntimeException: No application encryption key has been specified`

**Solution:**
```bash
php artisan key:generate
# Verify in .env
grep APP_KEY .env
```

### Config Cache Stale

**Error:** Changes to `.env` not taking effect

**Solution:** Clear config cache
```bash
php artisan config:clear
php artisan config:cache  # Cache again for production
```

### Timezone Issues

**Error:** Timestamps off by hours

**Cause:** APP_TIMEZONE mismatch

**Solution:**
```env
APP_TIMEZONE=Asia/Manila  # Metro Retail Philippines
```

Verify in code:
```php
>>> config('app.timezone')
=> "Asia/Manila"
>>> now()  # Should show Manila time
```

---

## Logging & Debugging

### Find Log Files

```bash
# Main app log
tail -f storage/logs/laravel.log

# Errors only
grep ERROR storage/logs/laravel.log | tail -20

# Search by date
grep "2026-06-09" storage/logs/laravel.log | head -50

# Orders log (if configured)
tail -f storage/logs/orders.log
```

### Enable Debug Mode (Dev Only)

```env
APP_ENV=local
APP_DEBUG=true
LOG_LEVEL=debug
```

**Never set `APP_DEBUG=true` in production** — exposes sensitive information

### Enable Query Logging

```env
# Add to .env
DB_LOG=true
```

Queries will be logged to `storage/logs/laravel.log`

---

## Testing & QA

### Run Tests

```bash
# All tests
php artisan test

# Specific test class
php artisan test --filter OrderControllerTest

# Verbose output
php artisan test -v

# With coverage
php artisan test --coverage
```

### Reset Database for Testing

```bash
# Refresh database (seeds included)
php artisan migrate:fresh --seed

# Clean slate for specific test
php artisan migrate:rollback
php artisan migrate
```

---

## Contacting Support

When reporting issues, include:

1. **Environment:**
   ```bash
   php -v
   composer show laravel/framework
   npm -v
   node -v
   ```

2. **Relevant logs:**
   ```bash
   tail -100 storage/logs/laravel.log
   ```

3. **Steps to reproduce**
4. **Expected vs. actual behavior**
5. **Screenshot** (if UI-related)

---

For environment setup, see [DEPLOYMENT.md](DEPLOYMENT.md)  
For database schema, see [DATABASE.md](DATABASE.md)  
For API endpoint details, see [API-REFERENCE.md](API-REFERENCE.md)
