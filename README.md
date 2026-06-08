# MarengEms — B2B2C Order Management System

**MarengEms** is Metro Retail's Order Management System for handling store-to-warehouse B2B orders and WooCommerce e-commerce orders end-to-end — from order creation and approval, through inventory allocation, to fulfillment tracking.

Built on Laravel 9. The project is named `ISO_Ordering_API` for historical reasons (its first integration was an ISO API), but the OMS is the core product.

---

## What It Does

### B2B Store Ordering
Store staff submit purchase orders against warehouse inventory. Each order goes through a regional approval workflow before fulfillment.

- Orders belong to a requesting store, reference a warehouse, and contain line items with pricing, quantity, and scheme (freebie/discount) details
- Approval is routed automatically based on the requesting store's region via `LocationConfig`
- Order status lifecycle: `new order → pending → processing → completed`
- Full notes/comment thread per order (`OrderNote`)
- PDF and Excel export for order documents

### E-Commerce Order Ingestion (WooCommerce)
Online orders placed on MarengEms Online (WooCommerce) are pushed here via webhook and processed as OMS orders in real time.

- Store and warehouse are resolved from order metadata (`_pickup_store`)
- Stock is validated before the order is committed — insufficient stock throws an error back to WooCommerce
- Freebie schemes (`12+1` buy-get-free) are calculated per item, payment-mode aware (`po15_scheme` vs `cash_bank_card_scheme`)
- Discount schemes (percentage or fixed amount) are applied per product
- Duplicate orders are rejected by SOF ID (`ATOM{order_number}-{YYYYMMDD}`)
- Inventory (cases + WMS virtual allocation in pieces) is deducted atomically after commit

### Inventory & Stock Management
- Per-store product tables (`products_{store_code}`) hold pricing, case pack, allocation, and scheme data
- `product_wms_allocations` tracks live virtual stock per SKU per warehouse
- Stock levels are updated in real time as orders are placed

### Oracle RMS Inventory Sync
Pulls live pricing and stock from Oracle RMS and pushes it to the WooCommerce storefront as CSV files over SSH — keeping the online catalog accurate.

- Supermarket allocation logic: 50% of SOH (only if SOH > 31 units), otherwise out-of-stock
- Department store: actual SOH
- Promo pricing and sale dates from `rpm_future_retail` are included in the sync

### User & Role Management
- Authentication via Laravel Fortify + Sanctum
- Role-based access (store manager, approver, admin, etc.)
- Store context switching (`SwitchUserContextController`, `SwitchRoleController`)
- Activity logging on key actions via `LogsActivity` trait

---

## Integrations

| Integration | Purpose |
|---|---|
| Oracle RMS | Inventory, pricing, transfers, BOL lookup |
| WooCommerce (Atom API) | Inbound e-commerce orders |
| iCard / Metro Bonus Card | Loyalty points balance and transaction history |
| OTP Service | Customer identity verification |
| ECR Terminals | Payment data ingestion |
| MRC Tender | Tender processing |
| ISO 8583 | Card network communication (via `ISO8583Client`) |

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 9 (PHP 8.0+) |
| Primary DB | MySQL |
| ERP / Inventory | Oracle RMS (read-only via `yajra/laravel-oci8`) |
| Auth | Laravel Fortify + Sanctum |
| Background Jobs | Laravel Queues |
| File Transfer | SSH/SFTP via `phpseclib3` |
| Reports | `barryvdh/laravel-dompdf`, `phpoffice/phpspreadsheet` |
| Payment Protocol | ISO 8583 (custom `ISO8583Client`) |

---

## Project Structure

```
app/
├── Console/Commands/       # Artisan commands (RMSSync, WmsMonitor, UpdateAllProductAllocations)
├── Http/
│   ├── Controllers/        # Feature controllers
│   │   ├── Icard/          # Loyalty & transaction controllers
│   │   ├── Auth/           # Account, password reset
│   ├── Middleware/         # Auth, CSRF, API token verification, security headers
│   ├── Requests/           # Form request validation
│   └── Responses/          # Custom Fortify responses
├── Jobs/                   # RMSSynchronizationJob, FetchAllocationJob, UpdateWmsAllocationsJob
├── Models/
│   ├── ISO_B2B/            # Order, OrderItem, OrderNote
│   └── Settings/           # SettingsWarehouse, SettingsStore, SettingsRegion, etc.
├── Services/               # OtpService, MRCTenderService, OracleRibXMLService
├── Helpers/                # ISO8583Client, JAK8583, SortHelper
├── Support/                # LocationConfig (store/warehouse mapping)
└── Traits/                 # LogsActivity
```

---

## API Routes Summary

| Method | URI | Controller | Auth |
|---|---|---|---|
| GET | `/api/iso-api/test` | TestController | — |
| POST | `/api/iso-api/get-user-data` | UserController | — |
| POST | `/api/iso-api/otp-send` | OtpController | — |
| POST | `/api/iso-api/otp-verify` | OtpController | — |
| POST | `/api/iso-api/loyalty-points` | UserPointsController | — |
| POST | `/api/iso-api/transactions` | TransactionHistoryController | — |
| POST | `/api/payment-data` | ECRController | — |
| POST | `/api/oracle-rms/item` | OracleRmsController | — |
| POST | `/api/v1/rms-sync/synchronize` | RMSCommerceSynchronizationController | — |
| GET | `/api/v1/rms-sync/status` | RMSCommerceSynchronizationController | — |
| GET | `/api/v1/rms-sync/logs` | RMSCommerceSynchronizationController | — |
| POST | `/api/mrc/tender` | MRCTenderController | — |
| POST | `/api/atom-api/` | AtomController | `verify.api.token` |
| POST | `/api/atom-api/test` | AtomController | — |
| GET | `/api/order-status/{storeOrderNo}/{sku}` | OracleTransferController | — |
| GET | `/api/order-bol/{tsf}/{sku}` | OracleTransferController | — |

---

## Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

### Required `.env` Variables

```env
# Application
APP_NAME=Laravel
APP_ENV=local
APP_URL=http://localhost

# MySQL
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_db
DB_USERNAME=root
DB_PASSWORD=

# Oracle RMS (read-only)
# Configure in config/database.php under 'oracle_rms'

# SSH / SFTP for RMS sync uploads
SFTP_HOST=
SFTP_PORT=22
SFTP_USER=
SFTP_PASS=
SFTP_DIR=/path/to/remote/csv/directory
```

---

## Running Locally (XAMPP)

```bash
# Install dependencies
composer install

# Run migrations
php artisan migrate

# Start queue worker (for background jobs)
php artisan queue:work

# Run RMS sync manually
php artisan rms:sync
```

---

## Key Conventions

- **Per-store product tables**: Products are stored in dynamic tables named `products_{store_code}` (e.g. `products_lz`, `products_stc`). Queries use `DB::table("products_{$storeCode}")`.
- **WMS allocation**: `product_wms_allocations` tracks virtual stock in pieces per warehouse. Deducted after each committed order.
- **SOF ID format**: `ATOM{woocommerce_order_number}-{YYYYMMDD}` for e-commerce orders.
- **Freebie schemes**: Stored as strings like `"12+1"` (buy 12, get 1 free). Payment mode determines which scheme column to use (`po15_scheme` vs `cash_bank_card_scheme`).
- **Location config**: All store-to-warehouse and store-to-region mappings live in `App\Support\LocationConfig`. Update here when adding new stores.
- **Activity logging**: Use the `LogsActivity` trait on models where audit trail is needed.
