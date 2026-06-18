# Agents Implementation Guide

Complete guide to using the Order Processing Agent and Frontend CSP Compliance Agent.

---

## Order Processing Agent

### Quick Start

```bash
# Manual processing (all pending orders)
php artisan order:process

# Process single order
php artisan order:process --order-id=999

# Process with batch limit
php artisan order:process --limit=50

# Dry-run (preview without committing)
php artisan order:process --dry-run

# Check for SLA breaches
php artisan order:check-sla
```

### Automatic Scheduling

Agents run automatically on configured cron schedules:

```
- Order processing: Every 5 minutes
- SLA checking: Every hour
```

To start the schedule worker:

```bash
php artisan schedule:work
# Or in production:
# 0 * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

### Configuration

Edit `.env`:

```env
# Enable/disable agent
ORDER_AGENT_ENABLED=true

# Risk score threshold for auto-approval (0-100)
ORDER_AGENT_RISK_THRESHOLD=50

# SLA enforcement (hours)
ORDER_AGENT_SLA_HOURS=24

# Notifications
ORDER_AGENT_NOTIFICATION_EMAIL=true
ORDER_AGENT_NOTIFY_WAREHOUSE=true
ORDER_AGENT_NOTIFICATION_EMAIL_ADDR=warehouse@metroretail.ph

# WMS deduction
ORDER_AGENT_AUTO_DEDUCT_WMS=true

# SLA checking
ORDER_AGENT_CHECK_SLA=true
```

Advanced config in `config/agents.php`:

```php
'order_processor' => [
    'auto_approve_threshold' => 50,
    'amount_thresholds' => [
        'default' => 10000,
        'lz' => 15000,      // Luzon (store 6012, etc.)
        'ntc' => 12000,     // North Cebu (store 6010)
        'stc' => 12000,     // South Cebu (stores 4002, 2008, 6009)
        'vs' => 10000,      // Visayas (stores 2010, 2017, 2019, 3018, 3019)
    ],
    'customer_risk_scores' => [
        'Platinum' => 0,
        'Gold' => 10,
        'Silver' => 20,
        'Bronze' => 30,
        'New' => 50,
    ],
]
```

### How It Works

#### 1. Risk Scoring

Risk score (0-100) is calculated from:
- **40% Order Amount** — Normalized against $50K max
- **30% Customer Tier** — Platinum (0) to New (50)
- **20% Store History** — Payment record, rejections
- **10% SKU** — New/discontinued products

#### 2. Auto-Approval Decision

Order is **auto-approved** if ALL conditions met:
```
✓ Risk score < threshold (default: 50)
✓ WMS allocation sufficient
✓ Order amount < regional threshold
✓ Customer tier >= Silver
✓ Store not suspended
```

#### 3. Escalation Flow

Orders that don't meet auto-approval are:
1. Routed to regional approver (via LocationConfig)
2. Email notification sent to approver
3. Status set to "for_approval"
4. Tracked for SLA (24 hours default)

#### 4. WMS Deduction

On auto-approval, pieces are atomically deducted:
```
UPDATE product_wms_allocations 
SET allocated_pieces = allocated_pieces - {qty}
WHERE sku = ... AND warehouse_code = ...
```

### Example Output

```
Starting order processing...

================================================================================
Order Processing Summary
================================================================================
Total orders processed: 47
Auto-approved:          32
Escalated:              12
Failed:                 3

Details:
  ✓ AUTO-APPROVED | ATOM12345-20260609 | Risk: 35 
  → ESCALATED | ATOM12346-20260609 | Risk: 65 → Store Manager (stc region)
  ✓ AUTO-APPROVED | ATOM12347-20260609 | Risk: 28
  ✗ FAILED | ATOM12348-20260609 | Stock unavailable; awaiting restock
================================================================================
```

### Logging

View agent logs:

```bash
# Real-time
tail -f storage/logs/order-agent.log

# Search for auto-approvals
grep "auto_approved" storage/logs/order-agent.log

# Search for SLA breaches
grep "SLA breach" storage/logs/order-agent.log

# Export for audit
cat storage/logs/order-agent.log | grep "2026-06-09"
```

### Troubleshooting

**Orders not being processed:**
```bash
# Check if agent is enabled
grep ORDER_AGENT_ENABLED .env

# Check if queue is running
php artisan queue:monitor

# Check logs
tail -50 storage/logs/order-agent.log
```

**Auto-approval threshold not being applied:**
```bash
# Verify config is cached
php artisan config:cache
php artisan config:clear  # Then re-cache
```

**Approver not receiving notification:**
- Check `ORDER_AGENT_NOTIFICATION_EMAIL=true`
- Check approver email in database: `SELECT * FROM users WHERE role='approver'`
- Verify MAIL config in `.env` (SMTP, host, port, auth)

---

## Frontend CSP Compliance Agent

### Quick Start

```bash
# Full audit (table format)
php artisan frontend:audit-csp

# JSON report (machine-readable)
php artisan frontend:audit-csp --format=json

# Markdown format (for documentation)
php artisan frontend:audit-csp --format=markdown

# Fail build if HIGH violations
php artisan frontend:audit-csp --fail-on=HIGH

# Fail only on CRITICAL violations
php artisan frontend:audit-csp --fail-on=CRITICAL

# Don't fail (report only)
php artisan frontend:audit-csp --fail-on=NONE
```

### Pre-Build Integration

Add to `package.json`:

```json
{
  "scripts": {
    "prebuild": "php artisan frontend:audit-csp --fail-on=HIGH",
    "build": "vite build"
  }
}
```

Now CSP audit runs automatically before building:

```bash
npm run build
# Runs: php artisan frontend:audit-csp --fail-on=HIGH
# Then: vite build
```

### CI/CD Integration

Add GitHub Actions workflow (`.github/workflows/csp-audit.yml`):

```yaml
name: CSP Compliance
on: [pull_request, push]
jobs:
  csp-audit:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - run: php artisan frontend:audit-csp --fail-on=MEDIUM
```

### Configuration

Edit `.env`:

```env
CSP_AGENT_ENABLED=true
CSP_AGENT_FAIL_SEVERITY=HIGH          # CRITICAL, HIGH, MEDIUM, LOW, NONE
CSP_AGENT_REPORT_FORMAT=table         # table, json, markdown
CSP_AGENT_CHECK_TEMPLATES=true
CSP_AGENT_CHECK_HEADERS=true
CSP_AGENT_CHECK_LIBRARIES=true
```

Advanced config in `config/agents.php`:

```php
'csp_auditor' => [
    'fail_on_severity' => 'HIGH',
    'report_path' => storage_path('reports/csp-audit.json'),
    
    'allowed_third_party_domains' => [
        'https://cdn.jsdelivr.net',
        'https://fonts.googleapis.com',
    ],
    
    'safe_libraries' => [
        'sweetalert2' => true,
        'axios' => true,
    ],
    
    'unsafe_libraries' => [
        'apexcharts' => 'unsafe-eval',
        'jquery' => 'unsafe-eval',
    ],
]
```

### What Gets Scanned

#### 1. CSP Header (`ContentSecurityPolicy` Middleware)

Checks for:
- ✓ 'unsafe-inline' in script-src / style-src (CRITICAL)
- ✓ 'unsafe-eval' in script-src (HIGH)
- ✓ Missing base-uri, form-action directives (MEDIUM)
- ✓ Weak directives ('*', 'blob:', etc.)

#### 2. Blade Templates

Scans for:
- ✓ Inline `<script>` tags (HIGH)
- ✓ Event handlers: onclick=, onload=, etc. (HIGH)
- ✓ Inline style= attributes (MEDIUM)
- ✓ Hardcoded external URLs not whitelisted (MEDIUM)

#### 3. Third-Party Libraries

Checks against:
- Known CSP-safe libraries: SweetAlert2, Axios, Tailwind CSS
- Known CSP-unsafe libraries: ApexCharts (eval), jQuery (eval)
- Recommendations for updates/alternatives

### Example Output

```
╔══════════════════════════════════════════════════════════════════╗
║ CSP Compliance Audit Report                                      ║
║ Generated: 2026-06-09T12:00:00Z                                  ║
╚══════════════════════════════════════════════════════════════════╝

[CSP HEADER AUDIT]
  ⚠ CRITICAL  script-src: Contains 'unsafe-inline'
    ↳ script-src 'unsafe-inline' defeats CSP protection. Move scripts to external files.

[TEMPLATE VIOLATIONS] (5 found)
  ⚠ HIGH  resources/views/orders/show.blade.php:42
    Code: <script>console.log('test')</script>
    Fix:  Move script to resources/js/, import with Vite, include via <script src=...>

  ⚠ MEDIUM  resources/views/dashboard.blade.php:15
    Code: style="color: red; font-size: 14px"
    Fix:  Use Tailwind CSS classes (text-red-500 text-sm)

[LIBRARY CHECK]
  ⚠  apexcharts (3.45.0)
    Issue: Uses 'unsafe-eval' directive
    Workaround: Add 'unsafe-eval' to script-src (not recommended)
    Recommendation: Consider chart.js or lightweight alternative

════════════════════════════════════════════════════════════════════

SUMMARY:
  Violations: 5 (1 CRITICAL, 2 HIGH, 2 MEDIUM)
  Risk Level: CRITICAL

RECOMMENDATIONS:
  1. Remove 'unsafe-inline' from script-src directive in ContentSecurityPolicy middleware
  2. Extract 5 inline script(s) to resources/js/ and import via Vite
  3. Replace 3 inline style(s) with Tailwind utility classes
  4. Update jQuery to 3.7.0+ or migrate to Vanilla JS
  5. Whitelist external CDNs in CSP header

════════════════════════════════════════════════════════════════════
```

### Fixing Violations

#### Inline Scripts

**Before:**
```blade
<div id="chart"></div>
<script>
    ApexCharts.render({...});
</script>
```

**After:**
```blade
<div id="chart"></div>
<script src="{{ asset('js/chart.js') }}"></script>

<!-- resources/js/chart.js -->
import ApexCharts from 'apexcharts';
ApexCharts.render({...});
```

#### Inline Styles

**Before:**
```blade
<div style="color: red; font-size: 14px; font-weight: bold;">Text</div>
```

**After:**
```blade
<div class="text-red-500 text-sm font-bold">Text</div>
```

#### Event Handlers

**Before:**
```blade
<button onclick="doSomething()">Click</button>
```

**After:**
```blade
<button id="my-button">Click</button>

<script>
    document.getElementById('my-button').addEventListener('click', doSomething);
</script>
```

#### External URLs

**Before:**
```blade
<img src="https://external-api.com/image.png" />
```

**After (Option 1: Use config):**
```blade
<img src="{{ config('services.cdn.url') }}/image.png" />
```

**After (Option 2: Whitelist in CSP):**
```php
// config/agents.php
'allowed_third_party_domains' => [
    'https://external-api.com',  // Add domain
]

// Then update CSP header:
// img-src 'self' https://external-api.com;
```

### Logging

View agent logs:

```bash
# Real-time
tail -f storage/logs/csp-agent.log

# JSON report
cat storage/reports/csp-audit.json | jq .

# Search violations
grep "script-src" storage/logs/csp-agent.log
```

### Troubleshooting

**Agent not detecting violations:**
```bash
# Verify it's enabled
grep CSP_AGENT_ENABLED .env  # Should be true

# Test manually
php artisan frontend:audit-csp --format=json
```

**Build is failing on CSP violations:**
```bash
# Adjust fail severity
php artisan frontend:audit-csp --fail-on=HIGH  # Less strict

# Or reduce in .env
CSP_AGENT_FAIL_SEVERITY=HIGH
```

**Third-party library showing as unsafe:**
1. Check library version — may be outdated
2. Review library's CSP documentation
3. Add to `safe_libraries` config if you've verified it's safe
4. Consider alternative library that's CSP-compatible

---

## Monitoring & Metrics

### View Agent Status

```bash
# Order agent status
php artisan order:process --dry-run --limit=10  # Preview next 10 orders

# CSP audit status
php artisan frontend:audit-csp --format=json | jq '.summary'
```

### Integration with External Monitoring

Log to external service (Datadog, Sentry, etc.):

```php
// config/logging.php
'datadog' => [
    'driver' => 'monolog',
    'level' => 'info',
    'handler' => \Monolog\Handler\...,
    'with' => [
        'apiKey' => env('DATADOG_API_KEY'),
    ]
]

// Use in agent:
Log::channel('datadog')->info('Order processed', $data);
```

### Metrics to Track

**Order Agent:**
- Auto-approvals per hour
- Escalations per hour
- Failure rate
- SLA breaches
- WMS deduction errors

**CSP Agent:**
- Violations per scan
- Risk level trend
- Library compatibility issues
- Build failures due to CSP

---

## Production Deployment

### 1. Environment Setup

```bash
# Copy template to .env (if not done)
cp .env.example .env

# Configure agents in .env
ORDER_AGENT_ENABLED=false
ORDER_AGENT_RISK_THRESHOLD=50
CSP_AGENT_ENABLED=true
CSP_AGENT_FAIL_SEVERITY=HIGH
```

### 2. Enable Schedule Worker

```bash
# Add cron job to crontab
0 * * * * cd /var/www/iso_ordering_api && php artisan schedule:run >> /dev/null 2>&1

# Or use systemd timer for Laravel Forge / Vapor
# See: https://laravel.com/docs/9.x/scheduling
```

### 3. Verify Scheduling

```bash
# Check if cron jobs are running
ps aux | grep schedule:work

# Or
ps aux | grep "php artisan"

# Check logs
tail -20 storage/logs/order-agent.log
```

### 4. Configure Notifications

Update email in `.env`:

```env
ORDER_AGENT_NOTIFICATION_EMAIL_ADDR=warehouse@metroretail.ph
MAIL_FROM_ADDRESS=noreply@metroretail.ph
MAIL_HOST=smtp.gmail.com
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
```

Test notifications:

```bash
php artisan tinker
>>> Mail::raw('Test', fn($m) => $m->to('test@example.com')->subject('CSP Test'))
```

---

## FAQ

**Q: Why is my order not auto-approving even though risk score is low?**
A: Check all conditions: stock available, amount < threshold, customer tier >= Silver, store active. Run with `--dry-run` to see the decision.

**Q: Can I manually override an auto-approval decision?**
A: Yes, approvers can still approve/reject any order. Agent auto-approval is just one path; human review is always possible.

**Q: How do I update the CSP header after fixing violations?**
A: Edit `app/Http/Middleware/ContentSecurityPolicy.php`, then re-audit with `php artisan frontend:audit-csp`.

**Q: Can agents be run on demand without cron?**
A: Yes, use CLI commands: `php artisan order:process`, `php artisan frontend:audit-csp`. Cron automates the schedule.

**Q: What happens if WMS deduction fails?**
A: Agent logs error, rolls back order approval, and logs alert for manual investigation.

**Q: Can I whitelist a third-party domain for CSP?**
A: Yes, add to `config/agents.csp_auditor.allowed_third_party_domains` or update CSP header directive.

---

See [docs/AGENTS.md](AGENTS.md) for detailed design specifications.
