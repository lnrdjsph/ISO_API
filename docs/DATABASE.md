# Database

Schema reference, connections, and migration patterns.

## Connections

Defined in `config/database.php`:

| Connection | Type | Purpose | Access |
|---|---|---|---|
| **mysql** (default) | MySQL | Orders, products, users, settings, logs | Read/Write |
| **oracle_rms** | Oracle 11g | Item master, pricing, SOH inventory | Read-only |
| **oracle_mbc** | Oracle 11g | iCard members, transactions (loyalty) | Read-only |
| **oracle_wms** | Oracle 11g | Transfers, BOL, warehouse tracking | Read-only |

---

## Core Schema (MySQL)

### `orders`

```sql
id                  INT UNSIGNED  PK AUTO_INCREMENT
sof_id              VARCHAR       -- "SOF202506-001" (manual) or "ATOM12345-20260609" (WooCommerce)
requesting_store    VARCHAR       -- Numeric store code: "6012", "4002"
requested_by        VARCHAR       -- User who submitted
mbc_card_no         VARCHAR       -- iCard 16-digit number
customer_name       VARCHAR
contact_number      VARCHAR
email               VARCHAR
channel_order       VARCHAR       -- "E-Commerce" (Atom) or SOF ID (manual)
warehouse           VARCHAR       -- Warehouse code: "80141", "80051"
time_order          VARCHAR       -- Order time
payment_center      VARCHAR       -- Payment center location
mode_payment        VARCHAR       -- "Cash", "Bank Transfer", "Card", "PO15%", etc.
payment_date        DATE
mode_dispatching    VARCHAR       -- "Delivery", "Pickup", etc.
delivery_date       DATE
address             TEXT
landmark            VARCHAR
order_status        VARCHAR       DEFAULT 'new order'
comment             TEXT
approval_document   VARCHAR       -- Path to signed approval PDF
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

**Status values:** `new order`, `for approval`, `approved`, `rejected`, `pending`, `processing`, `completed`, `cancelled`, `restored`, `archived`

**Note:** No FK constraint on `requesting_store` or `warehouse` — validated via LocationConfig at application level.

---

### `order_items`

```sql
id                  BIGINT UNSIGNED  PK AUTO_INCREMENT
order_id            UNSIGNED INT     FK → orders.id (CASCADE DELETE)
sku                 VARCHAR
item_description    VARCHAR
scheme              VARCHAR          -- "12+1", "Discount", etc.
price_per_pc        DECIMAL(10,2)    -- Price per individual piece
price               DECIMAL(10,2)    -- Price per case
qty_per_pc          INT              -- Pieces per case (case pack)
qty_per_cs          INT              -- Cases ordered
freebies_per_cs     VARCHAR          -- Freebies per case (stored as string, used as int)
discount            VARCHAR          -- "100" (fixed) or "15%" (percentage)
total_qty           INT              -- Total cases (MAIN) or freebie count (FREEBIE)
amount              DECIMAL(10,2)    -- Line total (price × qty_per_cs - discount)
remarks             VARCHAR          -- "Item Cancelled", notes, etc.
store_order_no      VARCHAR          -- Store's own order reference
item_type           VARCHAR          DEFAULT 'MAIN'   -- MAIN | FREEBIE | DISCOUNT
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

**item_type values:** `MAIN` (product ordered), `FREEBIE` (promo free item), `DISCOUNT` (discount line)

**Freebie item specifics:**
- `sku` = `freebie_sku` from product table (may differ from main item)
- `total_qty` = number of freebies earned
- `freebies_per_cs` = same as `total_qty`
- `amount` = 0
- `price_per_pc` / `price` = 0

---

### `order_notes`

```sql
id          BIGINT UNSIGNED  PK AUTO_INCREMENT
order_id    UNSIGNED INT     FK → orders.id (CASCADE DELETE)
user_id     BIGINT UNSIGNED  FK → users.id (nullable — null for agent/system notes)
status      ENUM             -- Status at time of note
note        TEXT
created_at  TIMESTAMP
updated_at  TIMESTAMP
```

**Status enum:** `new order`, `for approval`, `approved`, `rejected`, `pending`, `processing`, `completed`, `cancelled`, `restored`, `updated`, `failed`, `archived`

Relationship in `Order`: `->notes()` ordered by `created_at DESC`.

---

### `products_{storeCode}` (Dynamic — one per store)

Tables: `products_4002`, `products_2010`, `products_2017`, `products_2019`, `products_3018`, `products_3019`, `products_2008`, `products_6012`, `products_6009`, `products_6010`

```sql
id                         BIGINT UNSIGNED  PK AUTO_INCREMENT
sku                        VARCHAR          UNIQUE
description                VARCHAR
department_code            VARCHAR
department                 VARCHAR
case_pack                  VARCHAR          -- "12" or "12|24" (pipe-separated for multi-size)
srp                        DECIMAL(10,2)    -- Suggested retail price
allocation_per_case        INT              -- Cases available for this store
initial_allocation_per_case INT             -- Original allocation (reference)
cash_bank_card_scheme      VARCHAR(10)      -- "12+1" freebie for cash/bank/card
po15_scheme                VARCHAR(10)      -- "6+1" freebie for PO15% payments
discount_scheme            VARCHAR(10)      -- "15" (fixed) or "10%" (percentage)
freebie_sku                VARCHAR          -- SKU of the freebie product
archived_at                TIMESTAMP        NULL
archived_by                BIGINT UNSIGNED  NULL  FK → users.id
archive_reason             VARCHAR          NULL
created_at                 TIMESTAMP
updated_at                 TIMESTAMP
```

**Active products:** `whereNull('archived_at')`  
**Archived products:** `whereNotNull('archived_at')`

**Dynamic query pattern:**
```php
DB::table("products_{$storeCode}")
    ->where('sku', $sku)
    ->whereNull('archived_at')
    ->first();
```

---

### `product_wms_allocations`

```sql
sku                     VARCHAR(50)   -- Part of composite PK
warehouse_code          VARCHAR(10)   -- Part of composite PK (80141, 80051, etc.)
wms_actual_allocation   INT           -- Real stock from Oracle WMS (informational)
wms_virtual_allocation  INT           -- Available pieces (deducted by orders)
created_at              TIMESTAMP
updated_at              TIMESTAMP

PRIMARY KEY (sku, warehouse_code)
INDEX (warehouse_code)
```

**`wms_virtual_allocation`** is the operative field — orders deduct from this.  
**`wms_actual_allocation`** is updated by the WMS sync job and used as a reference.

**Deduction formula:**
```sql
UPDATE product_wms_allocations
SET wms_virtual_allocation = GREATEST(0, wms_virtual_allocation - {pieces})
WHERE sku = ? AND warehouse_code = ?
```

Pieces calculation: `total_qty × qty_per_pc`

---

### `activity_logs`

```sql
id           BIGINT UNSIGNED  PK
user_id      BIGINT UNSIGNED  FK → users.id
action       VARCHAR          -- 'approve', 'reject', 'login', 'import', etc.
description  VARCHAR
properties   JSON             -- Additional context metadata
ip_address   VARCHAR
user_agent   TEXT
url          VARCHAR
method       VARCHAR
created_at   TIMESTAMP
```

---

### `settings_stores`

```sql
id              BIGINT UNSIGNED  PK
store_code      VARCHAR(10)      UNIQUE  -- Numeric: "6012", "4002"
display_name    VARCHAR          -- "Super Metro Antipolo"
short_name      VARCHAR          -- Abbreviation
warehouse_code  VARCHAR(10)      -- FK to settings_warehouses
region_key      VARCHAR(50)      -- FK to settings_regions: "lz", "ntc", "stc", "vs"
status          VARCHAR          -- "active", "pending", "inactive"
is_active       BOOLEAN
```

Note: `status` is checked as `whereIn('status', ['active', 'pending'])` in LocationConfig.

---

### `settings_warehouses`

```sql
id              BIGINT UNSIGNED  PK
warehouse_code  VARCHAR(10)      UNIQUE  -- "80141", "80051"
name            VARCHAR          -- "Silangan Warehouse"
facility_id     VARCHAR          -- "SL", "CW", etc.
is_active       BOOLEAN
```

---

### `settings_regions`

```sql
id          BIGINT UNSIGNED  PK
region_key  VARCHAR(50)      UNIQUE  -- "lz", "ntc", "stc", "vs"
label       VARCHAR          -- "Luzon", "North Cebu"
is_active   BOOLEAN
```

---

### `settings_region_emails`

```sql
id          BIGINT UNSIGNED  PK
region_key  VARCHAR(50)      FK → settings_regions
email       VARCHAR          -- Email address OR sentinel "__approver__"
label       VARCHAR          -- Email label OR user_id string (when email = "__approver__")
is_active   BOOLEAN
```

**Approver lookup:**
```sql
SELECT label AS user_id
FROM settings_region_emails
WHERE region_key = 'stc' AND email = '__approver__'
LIMIT 1;
```

**All notification emails for a store** → `SettingsRegionEmail::emailsForStore($storeCode)` (resolved via region key).

---

### `users`

```sql
id                  BIGINT UNSIGNED  PK
name                VARCHAR
email               VARCHAR          UNIQUE
email_verified_at   TIMESTAMP        NULL
password            VARCHAR
role                VARCHAR          -- 'super admin', 'store manager', 'approver', 'warehouse manager', 'warehouse personnel'
location            VARCHAR          -- Store code, region key, or warehouse code depending on role
is_active           BOOLEAN
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

**`location` field interpretation by role:**
- Store manager → store code (`"6012"`)
- Approver → region key (`"stc"`)
- Warehouse roles → warehouse code (`"80051"`)
- Super admin → ignored

---

## Oracle Connections (Read-Only)

### `oracle_rms` — Item Master & Inventory

Used by `OracleRmsController` and `RMSCommerceSynchronizationController`.

Key tables (schema varies per Oracle instance):
```
ITEM_MASTER  — SKU, description, department, status
ITEM_LOC     — Store-level stock (SOH, price per store/location)
```

Query pattern:
```php
DB::connection('oracle_rms')->table('ITEM_MASTER')->where(...)->get();
```

### `oracle_mbc` — iCard Loyalty

Used by `app/Http/Controllers/Icard/` endpoints.

```
MEMBERS      — member_id, name, tier, status
TRANSACTIONS — transaction history, points earned
```

### `oracle_wms` — Warehouse Transfers & BOL

Used by `OracleTransferController`.

```
TRANSFERS  — tsf_id, item_code, from/to warehouse, quantity, status
BOL        — bol_no, tsf_id, created_date
```

---

## Migration Patterns

### Create table

```php
Schema::create('table_name', function (Blueprint $table) {
    $table->id();
    $table->string('sku', 50)->unique();
    $table->unsignedInteger('allocation_per_case')->default(0);
    $table->decimal('srp', 10, 2);
    $table->timestamp('archived_at')->nullable();
    $table->timestamps();

    $table->index('sku');
});
```

### Add column

```php
Schema::table('orders', function (Blueprint $table) {
    $table->string('approval_mode')->nullable()->after('approval_document');
});
```

### Commands

```bash
php artisan migrate                          # Run pending
php artisan migrate:rollback                 # Undo last batch
php artisan migrate:status                   # Check status
php artisan migrate:fresh --seed             # Reset + seed (dev only)
```

---

## Common Query Examples

```php
// Fetch order with items and notes
$order = Order::with(['items', 'notes.user'])->findOrFail($id);

// MAIN items only (for amount calculations)
$total = $order->items()->where('item_type', 'MAIN')->sum('amount');

// Check store-level stock
$cases = DB::table("products_{$storeCode}")
    ->where('sku', $sku)
    ->whereNull('archived_at')
    ->value('allocation_per_case');

// Check warehouse-level stock (with lock for concurrent writes)
$pieces = DB::table('product_wms_allocations')
    ->where('sku', $sku)
    ->where('warehouse_code', $warehouseCode)
    ->lockForUpdate()
    ->value('wms_virtual_allocation');

// Deduct from both tiers (MAIN item)
DB::table("products_{$storeCode}")
    ->where('sku', $sku)
    ->update(['allocation_per_case' => DB::raw("GREATEST(0, allocation_per_case - {$qtyCases})")]);

DB::table('product_wms_allocations')
    ->where('sku', $sku)
    ->where('warehouse_code', $warehouseCode)
    ->update(['wms_virtual_allocation' => DB::raw("GREATEST(0, wms_virtual_allocation - {$pieces})")]);

// Resolve approver for a store
$regionKey  = LocationConfig::regionForStore('6009');  // "stc"
$approverId = LocationConfig::regionApproverUserId('stc');  // 42
```

---

For business rules around stock operations, see [BUSINESS-CONTEXT.md](BUSINESS-CONTEXT.md)
For API endpoints, see [API-REFERENCE.md](API-REFERENCE.md)
