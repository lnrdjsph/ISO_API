# Agents Design

Architecture and specifications for Order Processing and Frontend CSP Compliance agents.

---

## Agent 1: Order Processing Agent

**Purpose:** Automate order approval decisions, route to approvers, notify warehouse, track SLA.

**Scope:** Orders in `new order` and `for approval` → approval decision

### Key Files

| File | Role |
|---|---|
| `app/Services/OrderProcessingService.php` | Core logic |
| `app/Console/Commands/OrderProcessCommand.php` | CLI: `php artisan order:process` |
| `app/Console/Commands/OrderCheckSlaCommand.php` | CLI: `php artisan order:check-sla` |
| `config/agents.php` | Configuration |
| `app/Console/Kernel.php` | Cron scheduling |

### Real Field Names

| Concept | Actual Field |
|---|---|
| Order status | `orders.order_status` (values with spaces: `"new order"`, `"for approval"`) |
| Warehouse | `orders.warehouse` (numeric: `"80051"`) |
| Store | `orders.requesting_store` (numeric: `"6012"`) |
| Payment mode | `orders.mode_payment` |
| Line item cases | `order_items.qty_per_cs` |
| Line item pieces/case | `order_items.qty_per_pc` |
| Line item total qty | `order_items.total_qty` |
| Line amount | `order_items.amount` |
| Item type | `order_items.item_type` (`MAIN`, `FREEBIE`, `DISCOUNT`) |
| Store stock | `products_{store}.allocation_per_case` (cases) |
| Warehouse stock | `product_wms_allocations.wms_virtual_allocation` (pieces) |

### Auto-Approval Criteria

```
Auto-approve if ALL of:
  ✓ order_status IN ['new order', 'for approval']
  ✓ risk_score < threshold (default 50)
  ✓ wms_virtual_allocation sufficient for all MAIN items
  ✓ allocation_per_case sufficient for all MAIN items
  ✓ MAIN items sum(amount) < regional threshold
  ✓ customerScore ≤ 30 (at least some order history)
```

### Risk Scoring (0–100)

```
score = amount(40%) + customer(30%) + store(20%) + sku(10%)

Amount score  = min((sum(item.amount where MAIN) / 50000) × 100, 100)

Customer score = based on completed orders in past 3 months for this store
  > 10  → Gold  (10)
  > 5   → Silver (20)
  > 0   → Bronze (30)
  = 0   → New   (50)

Store score = based on rejected orders in past 3 months
  > 2   → 30
  > 0   → 20
  = 0   → 10
  Not in LocationConfig → 50

SKU score = 0 (extend to flag new/discontinued SKUs)
```

### Two-Tier Stock Deduction (on auto-approve)

```
Tier 1 — products_{storeCode}.allocation_per_case:
  - MAIN items only
  - GREATEST(0, allocation_per_case - qty_per_cs)

Tier 2 — product_wms_allocations.wms_virtual_allocation:
  - ALL items (MAIN + FREEBIE)
  - GREATEST(0, wms_virtual_allocation - (total_qty × qty_per_pc))
```

Mirrors `OrderController::deductAllocationStock()` exactly.

### Audit Note on Auto-Approve

An `order_notes` record is always created:

```php
OrderNote::create([
    'order_id' => $order->id,
    'user_id'  => null,  // null = system/agent
    'status'   => 'approved',
    'note'     => "Auto-approved by Order Processing Agent. Risk score: {$score}/100.",
]);
```

### Workflow

```
AGENT RUN (every 5 min or manual)
│
├─ Query: order_status IN ['new order', 'for approval']
│
├─ For each order:
│  ├─ Guard: status still pending? (re-check after query)
│  ├─ hasStockAvailable()
│  │   ├─ Check allocation_per_case ≥ qty_per_cs for MAIN items
│  │   └─ Check wms_virtual_allocation ≥ total_qty × qty_per_pc for MAIN items
│  │
│  ├─ calculateRiskScore() → 0-100
│  ├─ shouldAutoApprove()  → bool
│  │
│  ├─ TRUE → approve()
│  │   ├─ update order_status = 'approved'
│  │   ├─ deductStock() — both tiers, wrapped in DB::transaction
│  │   ├─ OrderNote::create(status='approved', user_id=null)
│  │   └─ notifyWarehouse()
│  │
│  └─ FALSE → escalate()
│      ├─ update order_status = 'for approval'
│      ├─ LocationConfig::regionForStore() → regionKey
│      ├─ LocationConfig::regionApproverUserId() → approverId
│      ├─ OrderNote::create(status='for approval', user_id=null)
│      └─ notifyApprover()
│
└─ Return summary: {total, auto_approved, escalated, skipped, failed}
```

### Triggers & Scheduling

```bash
php artisan order:process                    # All pending
php artisan order:process --order-id=999    # Single order
php artisan order:process --limit=50        # Batch
php artisan order:process --dry-run         # Preview, no writes
php artisan order:check-sla                 # Flag SLA breaches
```

**Kernel.php schedule:**
```php
$schedule->command('order:process', ['--limit' => 100])->everyFiveMinutes();
$schedule->command('order:check-sla')->hourly();
```

### Configuration (`config/agents.php`)

```php
'order_processor' => [
    'enabled'                    => true,
    'auto_approve_threshold'     => 50,    // risk score 0-100
    'sla_hours'                  => 24,
    'notification_email'         => true,
    'notify_warehouse'           => true,
    'notification_email_address' => 'warehouse@metroretail.ph',
    'enable_auto_deduction'      => true,
    'check_sla_enabled'          => true,

    'risk_weights' => [
        'amount' => 0.4, 'customer' => 0.3, 'store' => 0.2, 'sku' => 0.1,
    ],

    // Region keys match LocationConfig
    'amount_thresholds' => [
        'default' => 10000,
        'lz'  => 15000,   // Luzon: store 6012
        'ntc' => 12000,   // North Cebu: store 6010
        'stc' => 12000,   // South Cebu: stores 4002, 2008, 6009
        'vs'  => 10000,   // Visayas: stores 2010, 2017, 2019, 3018, 3019
    ],

    'customer_risk_scores' => [
        'Gold' => 10, 'Silver' => 20, 'Bronze' => 30, 'New' => 50,
    ],

    'log_channel' => 'order_agent',
]
```

### Error Handling

| Scenario | Behavior |
|---|---|
| Stock insufficient | `skipped` — do not change status; leave for restock |
| No approver configured | `escalated` with null approver_id; log warning |
| DB transaction fails | Exception propagated; counted as `failed` in summary |
| Email send fails | Logged as warning; does not fail the approval |
| Status already changed | `skipped` — race condition guard |

### Logs

```bash
tail -f storage/logs/order-agent.log
grep "auto_approved" storage/logs/order-agent.log
grep "SLA breach" storage/logs/order-agent.log
```

---

## Agent 2: Frontend CSP Compliance Agent

**Purpose:** Audit Content Security Policy compliance across templates, headers, and libraries.

**Scope:** `ContentSecurityPolicy` middleware + `resources/views/**/*.blade.php` + `package.json`

### Key Files

| File | Role |
|---|---|
| `app/Services/CspComplianceAuditService.php` | Core audit logic |
| `app/Console/Commands/FrontendAuditCspCommand.php` | CLI: `php artisan frontend:audit-csp` |
| `app/Http/Middleware/ContentSecurityPolicy.php` | What's audited |
| `config/agents.php` | Configuration |

### What Gets Scanned

#### 1. CSP Header (`ContentSecurityPolicy` middleware)

| Check | Severity |
|---|---|
| `script-src 'unsafe-inline'` | CRITICAL |
| `script-src 'unsafe-eval'` | HIGH |
| `style-src 'unsafe-inline'` | HIGH |
| Missing `base-uri` directive | MEDIUM |
| Missing `form-action` directive | MEDIUM |

#### 2. Blade Templates (`resources/views/**/*.blade.php`)

| Violation | Severity | Fix |
|---|---|---|
| Inline `<script>...</script>` | HIGH | Move to `resources/js/`, import via Vite |
| Event handlers `onclick=`, `onload=` | HIGH | Use `addEventListener()` in external JS |
| `style="..."` attributes | MEDIUM | Replace with Tailwind utility classes |
| Hardcoded external URLs (not whitelisted) | MEDIUM | Use `config()` helper or whitelist in CSP |

#### 3. Third-Party Libraries (`package.json`)

| Library | Issue | Directive Needed |
|---|---|---|
| `apexcharts` | Uses `eval()` for rendering | `unsafe-eval` |
| `jquery` (old versions) | Uses `eval()` | `unsafe-eval` |
| `sweetalert2` | ✓ CSP-safe | — |
| `axios` | ✓ CSP-safe | — |
| `tailwindcss` | ✓ CSP-safe | — |

### Severity Levels

| Level | Definition | Default action |
|---|---|---|
| **CRITICAL** | Defeats entire CSP (e.g., `*` origin, `unsafe-inline` on script) | Always fail build |
| **HIGH** | Requires `unsafe-*` directive | Fail by default |
| **MEDIUM** | Best-practice violation | Warn only |
| **LOW** | Code quality concern | Log only |

### Workflow

```
AGENT RUN (manual or pre-build)
│
├─ auditCspHeader()
│   └─ Read app/Http/Middleware/ContentSecurityPolicy.php
│   └─ Regex check all directives
│
├─ scanTemplates()
│   └─ Glob resources/views/**/*.php
│   └─ Per file: check inline scripts, handlers, styles, external URLs
│
├─ checkLibraries()
│   └─ Read package.json
│   └─ Cross-reference against safe_libraries / unsafe_libraries config
│
└─ generateReport()
    └─ Determine risk level (CRITICAL > HIGH > MEDIUM > LOW)
    └─ Generate recommendations[]
    └─ should_fail = violations at/above fail_on_severity
    └─ Write JSON report to storage/reports/csp-audit.json
    └─ Return report array
```

### CLI Usage

```bash
php artisan frontend:audit-csp                      # Table output
php artisan frontend:audit-csp --format=json        # JSON report
php artisan frontend:audit-csp --format=markdown    # Markdown (for CI logs)
php artisan frontend:audit-csp --fail-on=HIGH       # Exit 1 on HIGH+ violations
php artisan frontend:audit-csp --fail-on=NONE       # Report only, never fail
```

### Pre-Build Integration

```json
// package.json
"scripts": {
    "prebuild": "php artisan frontend:audit-csp --fail-on=HIGH",
    "build": "vite build"
}
```

### Configuration (`config/agents.php`)

```php
'csp_auditor' => [
    'enabled'           => true,
    'fail_on_severity'  => 'HIGH',        // CRITICAL | HIGH | MEDIUM | LOW | NONE
    'report_format'     => 'table',       // json | table | markdown
    'report_path'       => storage_path('reports/csp-audit.json'),
    'check_templates'   => true,
    'check_headers'     => true,
    'check_libraries'   => true,

    'template_paths' => [resource_path('views')],

    'allowed_third_party_domains' => [
        'https://cdn.jsdelivr.net',
        'https://fonts.googleapis.com',
        'https://fonts.gstatic.com',
        'https://unpkg.com',
    ],

    'safe_libraries' => [
        'sweetalert2' => true,
        'axios'       => true,
        'tailwindcss' => true,
    ],

    'unsafe_libraries' => [
        'apexcharts' => 'unsafe-eval',
        'jquery'     => 'unsafe-eval',
    ],

    'log_channel' => 'csp_agent',
]
```

### Sample Report Output

```
╔══════════════════════════════════════════════════════════════════╗
║ CSP Compliance Audit — 2026-06-09T12:00:00Z                     ║
╚══════════════════════════════════════════════════════════════════╝

[HEADER AUDIT]
  ⚠ CRITICAL  script-src: Contains 'unsafe-inline'
    ↳ Defeats script CSP. Move inline scripts to resources/js/.

[TEMPLATE VIOLATIONS] (5 found)
  ⚠ HIGH    /resources/views/orders/show.blade.php:42
    Code: <script>ApexCharts.render(...);</script>
    Fix:  Move to resources/js/modules/orders.js, import via Vite

  ⚠ MEDIUM  /resources/views/dashboard.blade.php:15
    Code: style="color: red; font-size: 14px"
    Fix:  Use Tailwind classes: text-red-500 text-sm

[LIBRARY CHECK]
  ⚠ HIGH    apexcharts@3.45.0 — Uses eval() (unsafe-eval required)
  ✓  OK     sweetalert2@11.0.0 — CSP-safe

════════════════════════════════════════════════════════════════════
SUMMARY: 5 violations (1 CRITICAL, 2 HIGH, 2 MEDIUM) — Risk: CRITICAL
RECOMMENDATIONS:
  1. Remove 'unsafe-inline' from script-src
  2. Extract 2 inline scripts to resources/js/
  3. Replace 3 inline styles with Tailwind classes
  4. Consider chart.js over ApexCharts (avoids unsafe-eval)
════════════════════════════════════════════════════════════════════
Exit: 1 (FAIL — HIGH violations above threshold)
```

### Logs

```bash
tail -f storage/logs/csp-agent.log
cat storage/reports/csp-audit.json | php -r "echo json_encode(json_decode(file_get_contents('php://stdin')), JSON_PRETTY_PRINT);"
```

---

## Shared Infrastructure

### Logging Channels (`config/logging.php`)

```php
'order_agent' => ['driver' => 'daily', 'path' => storage_path('logs/order-agent.log'), 'days' => 30],
'csp_agent'   => ['driver' => 'daily', 'path' => storage_path('logs/csp-agent.log'),   'days' => 30],
```

### Environment Variables (`.env`)

```env
# Order Agent
ORDER_AGENT_ENABLED=true
ORDER_AGENT_RISK_THRESHOLD=50
ORDER_AGENT_SLA_HOURS=24
ORDER_AGENT_NOTIFICATION_EMAIL=true
ORDER_AGENT_NOTIFY_WAREHOUSE=true
ORDER_AGENT_NOTIFICATION_EMAIL_ADDR=warehouse@metroretail.ph
ORDER_AGENT_AUTO_DEDUCT_WMS=true
ORDER_AGENT_CHECK_SLA=true

# CSP Agent
CSP_AGENT_ENABLED=true
CSP_AGENT_FAIL_SEVERITY=HIGH
CSP_AGENT_REPORT_FORMAT=json
CSP_AGENT_CHECK_TEMPLATES=true
CSP_AGENT_CHECK_HEADERS=true
CSP_AGENT_CHECK_LIBRARIES=true
```

---

See [AGENTS_IMPLEMENTATION.md](AGENTS_IMPLEMENTATION.md) for usage guide and CI/CD integration.
