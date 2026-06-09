# Architecture

High-level system design and component interactions.

## System Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                    MarengEms (ISO_Ordering_API)                 │
│                    Laravel 9 + MySQL + Oracle                   │
└─────────────────────────────────────────────────────────────────┘

INBOUND CHANNELS                  CORE SYSTEM                  OUTBOUND INTEGRATIONS
─────────────────────────────────────────────────────────────────────────────────────
WooCommerce webhook ──────┐       ┌──────────────┐            ┌─── Oracle RMS SFTP
(Atom API)                 │       │  Order API   │            │    (synced pricing/stock)
                           │       │  & Web UI    │            │
Manual B2B form ───────────┼──────▶│  (Blade +    │───────────▶│─── ISO 8583 Card Host
                           │       │   Vite)      │            │    (jPOS)
ECR Terminal POST ─────────┤       │              │            │
                           │       │ Powered by   │            └─── MRC Tender API
Oracle RMS query ─────────┘       │ - Fortify    │
                                  │ - Sanctum    │
iCard (Oracle MBC) ────────────────│ - Queue     │
                                  │ - Filesystem│
Oracle WMS allocations ─────────────│              │
                                  └──────────────┘
```

## Layered Architecture

### 1. **Routing Layer** (`routes/`)

- **`web.php`** — Session-authenticated web routes
  - `/b2b2c/orders/*` — Order CRUD, approval, printing
  - `/b2b2c/products/*` — Catalog management
  - `/b2b2c/forms/*` — SOF/ROF submission
  - `/reports/*` — Analytics and exports
  - `/settings/*` — Configuration (region, store, warehouse)
  - `/users/*` — User CRUD

- **`api.php`** — API routes (public, token, or no auth)
  - `POST /api/atom-api/` — WooCommerce webhook (Bearer token)
  - `POST /api/iso-api/*` — Legacy iCard endpoints (public)
  - `GET /api/oracle-rms/*` — RMS queries (public)
  - `POST /api/v1/rms-sync/*` — RMS sync endpoints (no auth)
  - `POST /api/payment-data` — ECR terminal (no auth)
  - `POST /api/mrc/tender` — MRC tender (no auth)

### 2. **Controller Layer** (`app/Http/Controllers/`)

Pattern: Controller receives request → validates → delegates to Service/Model → returns response

**Key controllers:**
- `OrderController` — CRUD, approval/rejection, printing (SOF, invoice, freebies)
- `AtomController` — WooCommerce webhook receiver (deduplication, stock check, allocation deduction)
- `ProductController` — Catalog CRUD, import/export, WMS allocation updates
- `RMSCommerceSynchronizationController` — RMS sync orchestrator (pricing, stock, CSV generation, SFTP upload)
- `OracleRmsController` — Direct RMS queries (product, stock, pricing)
- `OracleTransferController` — BOL lookup, transfer status from Oracle WMS
- `SettingsController` — Store/warehouse/region configuration + LocationConfig invalidation
- `UserController`, `DashboardController`, `ReportController` — Standard CRUD / analytics

**Middleware applied:**
- Session auth (web routes) via `web` middleware group
- API token via `VerifyApiToken` (Atom endpoints only)
- Session expiry via `RedirectIfSessionExpired`
- Activity logging via `LogsActivity` trait per controller

### 3. **Domain Model Layer** (`app/Models/`)

**ISO_B2B domain:**
- `Order` — Order record with `status` (new, for_approval, approved, rejected, processing, completed)
  - Belongs to approver via `LocationConfig::regionApproverUserId()`
  - Has many `OrderItem`, `OrderNote`
  - Filterable by user role and location
  
- `OrderItem` — Line item with SKU, qty, pricing, scheme (freebie/discount format)
  - Tracks `item_type` (MAIN or FREEBIE)
  - `pieces_per_case`, `freebies_per_case` for case/piece quantities
  
- `OrderNote` — Comment thread, ordered by creation time

**Settings domain:**
- `SettingsStore` — Store master (code, name, warehouse_code, region_key)
- `SettingsWarehouse` — Warehouse master (code, name, facility_id)
- `SettingsRegion` — Region master (region_key, label)
- `SettingsRegionEmail` — Regional data: approver email rows (`__approver__` sentinel in email column, user_id in label column)

**Other models:**
- `User` — Auth, role assignment
- `Product` — Soft-deletable product archive
- `ActivityLog` — Audit trail (user_id, action, description, properties JSON, IP, UA)

### 4. **Service Layer** (`app/Services/`)

- `OtpService` — 3-minute OTP generation/verification (Asia/Manila timezone)
- `MRCTenderService` — MRC payment processing
- `OracleRibXMLService` — Oracle RIB XML formatting for transfers

### 5. **Support & Helpers**

**LocationConfig** (`app/Support/LocationConfig.php`):
- Singleton caching layer for store/warehouse/region/approver mappings
- Reads from `settings_*` tables on first access
- Falls back to static config if DB unavailable
- **24-hour cache TTL** — invalidated by `SettingsController` on write
- Public API: `regions()`, `storeWarehouse()`, `regionApproverUserId()`, `regionStores()`, etc.

**ISO8583Client** (`app/Helpers/ISO8583Client.php`):
- Custom MTI-based message builder for card payments
- Fields: amount, STAN, POS mode, track data, terminal ID, merchant ID
- TCP socket connection to jPOS host
- Response parsing: field 39 (response code), field 37 (RRN)

**JAK8583** (`app/Helpers/JAK8583.php`):
- ISO 8583 field encoder/builder

**SortHelper** (`app/Helpers/SortHelper.php`):
- Sorting utilities

### 6. **Background Jobs** (`app/Jobs/`)

Queued via Laravel Queue (default: database driver in .env):

- `RMSSynchronizationJob` — Fetches latest RMS pricing/stock, generates CSV, uploads via SFTP
- `FetchAllocationJob` — Pulls WMS allocations from Oracle
- `UpdateWmsAllocationsJob` — Updates `product_wms_allocations` table with live counts

**Triggered by:**
- Manual `/b2b2c/products/fetch-allocations` button
- Scheduled via `schedule:run` if defined in `app/Console/Kernel.php`

### 7. **Database Layer** (`config/database.php`)

**Connections:**
- **`mysql`** (default) — Orders, products, users, activity logs, settings
- **`oracle_rms`** — Read-only ERP (pricing, inventory, item master)
- **`oracle_mbc`** — Read-only iCard loyalty, transactions
- **`oracle_wms`** — Virtual stock, allocation tracking (read-write)

**Key dynamic tables:**
- `orders`, `order_items`, `order_notes` — Order data
- `products_{store_code}` (e.g., `products_lz`, `products_stc`) — Per-store product catalogs
- `product_wms_allocations` — Virtual stock in pieces per SKU per warehouse
- `activity_logs` — Audit trail
- `settings_stores`, `settings_warehouses`, `settings_regions`, `settings_region_emails` — Configuration

### 8. **Frontend Assets** (`resources/`)

**Vite** build tool with:
- **Tailwind CSS** — Utility-first styling + plugins (forms, typography, aspect-ratio)
- **ApexCharts** — Charts for reports (bar, line, pie)
- **jQuery** — DOM manipulation (legacy)
- **SweetAlert2** — Modals/alerts
- **Axios** — AJAX HTTP client

**Development:** `npm run dev` (watch mode, HMR)  
**Production:** `npm run build` (minified, hashed assets)

## Data Flow: Order Creation (WooCommerce → OMS)

```
1. WooCommerce webhook (JSON) → POST /api/atom-api/
   
2. AtomController::receiveOrder()
   ├─ Validate Bearer token (VerifyApiToken middleware)
   ├─ Parse order JSON, normalize field names
   ├─ Extract store code from _pickup_store meta
   ├─ Query products_{store_code} for pricing/scheme
   ├─ Check WMS allocation sufficiency
   ├─ Deduplicate by SOF ID (ATOM{order_no}-{YYYYMMDD})
   ├─ Create Order + OrderItems atomically
   └─ Deduct pieces from product_wms_allocations

3. Order saved → activity logged → response sent (201 or 409 conflict)
```

## Data Flow: RMS Sync (Excel → SFTP)

```
1. Button click: "Fetch Allocations" or cron job
   
2. RMSCommerceSynchronizationController::synchronize()
   ├─ Query Oracle RMS for live item master (pricing, SOH)
   ├─ Apply allocation logic per store type:
   │  ├─ Supermarket: 50% SOH, min 31 units (else out-of-stock)
   │  └─ Dept store: full SOH
   ├─ Generate CSV (itemlist.csv, stores.csv) with allocated stock
   ├─ Connect via SFTP to WooCommerce server
   ├─ Upload CSVs
   └─ Keep backup + logs in storage/app/sync/

3. WooCommerce imports CSV → updates product stock in real-time
```

## Data Flow: Order Approval

```
1. Manager views order in dashboard → clicks "Approve" button
   
2. OrderController::approve()
   ├─ Verify user is approver for order's region (via LocationConfig)
   ├─ Validate order status = "for_approval"
   ├─ Update status to "approved"
   ├─ Send OrderApprovalRequestMail (if configured)
   ├─ Log activity (user_id, action='approve', properties={order_id, ...})
   └─ Return response

3. Order now visible to warehouse manager in "approved" filter
```

## Integration Points

### WooCommerce (Inbound Webhook)
- **Endpoint:** `POST /api/atom-api/` (Bearer token)
- **Payload:** Order JSON with meta fields
- **Actions:** Create Order, deduct allocation, log activity

### Oracle RMS (Inbound Read-Only)
- **Connection:** `oracle_rms` DB connection
- **Tables:** Item master, pricing, stock-on-hand
- **Usage:** Product catalog, pricing lookups, sync source

### Oracle MBC (Inbound Read-Only)
- **Connection:** `oracle_mbc` DB connection
- **Tables:** iCard member data, transaction history
- **Usage:** Loyalty endpoints, points/transactions APIs

### Oracle WMS (Bidirectional)
- **Connection:** `oracle_wms` DB connection
- **Tables:** Virtual allocations
- **Usage:** Stock deduction, allocation tracking, transfer status

### ISO 8583 (Card Payments)
- **Client:** `ISO8583Client` + `JAK8583`
- **Host:** jPOS (configurable IP/port in `.env`)
- **Protocol:** TCP socket, MTI-based messages
- **Actions:** Build request (0200), parse response (field 39, 37)

### SFTP (RMS Sync Export)
- **Library:** `phpseclib3`
- **Credentials:** `.env` (`SFTP_HOST`, `SFTP_USER`, `SFTP_PASS`)
- **Actions:** Connect, upload CSV files to WooCommerce server

### MRC Tender
- **Endpoint:** Receives tender data from MRC system
- **Service:** `MRCTenderService` processes payment

### ECR Terminals
- **Endpoint:** `POST /api/payment-data`
- **Payload:** Terminal payment records (no auth)
- **Actions:** Log and process payment data

## Concurrency & Atomicity

- **Order creation:** Atomic transaction (Order + OrderItems + allocation deduction in single DB transaction)
- **RMS sync:** Non-blocking job queue (doesn't hold locks)
- **Session handling:** Fortify/Sanctum manage concurrency; no custom locking
- **WMS allocations:** Last-write-wins (timestamp-based conflict resolution if needed)

## Deployment Model

- **Web server:** Apache/Nginx + PHP-FPM (Laravel Forge / XAMPP for dev)
- **Database:** MySQL (primary), Oracle connections (RMS, MBC, WMS)
- **Queue:** Database or Redis (configurable in `.env`)
- **File storage:** Local filesystem (`storage/app/`)
- **Frontend:** Pre-built assets in `public/` (Vite output)

**Stateless design:** Each request is independent; session state stored in DB or Redis.

---

*For database schema details, see [DATABASE.md](DATABASE.md)*  
*For API endpoint details, see [API-REFERENCE.md](API-REFERENCE.md)*  
*For business rules, see [BUSINESS-CONTEXT.md](BUSINESS-CONTEXT.md)*
