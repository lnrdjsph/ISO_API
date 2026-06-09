# Business Context

Domain language, order lifecycle, and business rules.

## Glossary

| Term | Definition |
|---|---|
| **SOF** | Sales Order Form — formal order document; generated ID format: `SOF{YYYYMM}-{NNN}` (manual) or `ATOM{woo_order_no}-{YYYYMMDD}` (WooCommerce) |
| **OMS** | Order Management System — this system |
| **B2B** | Business-to-Business (store→warehouse orders) |
| **B2C** | Business-to-Consumer (WooCommerce e-commerce orders) |
| **BOL** | Bill of Lading — shipment document from Oracle WMS |
| **WMS** | Warehouse Management System (Oracle WMS) — virtual/actual stock |
| **RMS** | Retail Management System (Oracle RMS) — inventory master, pricing source |
| **iCard / MBC** | Loyalty program (Oracle MBC) — member points, transaction history |
| **Scheme** | Freebie or discount on a product (e.g., `"12+1"` = buy 12 cases, get 1 free) |
| **Allocation** | Cases available for a store (`allocation_per_case` in `products_{store}`) |
| **Virtual Allocation** | Pieces available in warehouse (`wms_virtual_allocation` in `product_wms_allocations`) |
| **Store Code** | Numeric identifier matching Oracle RMS (e.g., `6012`, `4002`, `2008`) |
| **Region Key** | Short string grouping stores (e.g., `lz`, `ntc`, `stc`, `vs`) |
| **Warehouse Code** | Numeric warehouse ID (e.g., `80141`, `80051`, `80191`) |
| **SKU** | Stock Keeping Unit — product identifier |
| **Cases (cs)** | Bulk unit of ordering (e.g., 1 case = 12 pieces) |
| **Pieces (pc)** | Individual unit |
| **SRP** | Suggested Retail Price — from product table |
| **Case Pack** | Pieces per case — stored in `products_{store}.case_pack` (can be `"12\|24"` for multi-size) |
| **item_type** | Order line classification: `MAIN` (product), `FREEBIE` (free promo item), `DISCOUNT` (discount line) |

---

## Order Lifecycle

```
                      ┌──────────────────────────────────────┐
                      │  CREATED (order_status: "new order") │
                      │  - Manual SOF or WooCommerce webhook  │
                      │  - SOF ID assigned                    │
                      │  - Items + stock deducted atomically  │
                      └──────────────────┬───────────────────┘
                                         │
                             forApproval()│
                                         ▼
                      ┌──────────────────────────────────────┐
                      │  FOR APPROVAL ("for approval")        │
                      │  - Email sent to regional approver    │
                      │  - Approver sees in dashboard         │
                      └────────┬──────────────┬──────────────┘
                               │              │
                      approve()│              │rejectOrder()
                               ▼              ▼
               ┌───────────────────┐  ┌──────────────────────┐
               │  APPROVED         │  │  REJECTED             │
               │  ("approved")     │  │  ("rejected")         │
               │  - PDF generated  │  │  - Stock reverted     │
               │  - Warehouse notif│  │  - Can re-submit      │
               └────────┬──────────┘  └──────────────────────┘
                        │
              complete()│
                        ▼
               ┌─────────────────────┐
               │  COMPLETED          │
               │  ("completed")      │
               └─────────────────────┘

At any point:
  cancel()   → "cancelled"   (stock reverted)
  archive()  → "archived"    (stock reverted)
  restore()  → "new order"   (stock re-deducted)
```

**Full status list:** `new order`, `for approval`, `approved`, `rejected`, `pending`, `processing`, `completed`, `cancelled`, `restored`, `archived`

---

## Key Order Fields

```php
// orders table
$order->sof_id           // "SOF202506-001" or "ATOM12345-20260609"
$order->requesting_store // Numeric store code: "6012", "4002"
$order->warehouse        // Numeric warehouse code: "80141", "80051"
$order->order_status     // Current lifecycle status
$order->mode_payment     // "Cash", "Bank Transfer", "Card", "PO15%", etc.
$order->mode_dispatching // "Delivery", "Pickup", etc.
$order->payment_date     // Date
$order->delivery_date    // Date
$order->channel_order    // "E-Commerce" for WooCommerce; SOF ID for manual
$order->requested_by     // User who submitted
$order->mbc_card_no      // iCard number (16-digit)
$order->approval_document // Path to signed PDF
$order->comment          // Freeform notes on the order

// order_items table
$item->order_id          // FK to orders
$item->sku               // Product code
$item->item_description  // Product name
$item->item_type         // "MAIN", "FREEBIE", or "DISCOUNT"
$item->price_per_pc      // Price per piece
$item->price             // Price per case
$item->qty_per_pc        // Pieces per case (case pack)
$item->qty_per_cs        // Number of cases ordered
$item->total_qty         // Total cases (MAIN) or total freebies (FREEBIE)
$item->freebies_per_cs   // Freebies per case
$item->scheme            // Scheme string: "12+1", "Discount", etc.
$item->discount          // Discount amount ("100") or percentage ("15%")
$item->amount            // Total amount (price × qty_per_cs - discount)
$item->remarks           // "Item Cancelled", discount notes, etc.
$item->store_order_no    // Store-assigned reference number
```

---

## Order Note (Audit Trail)

Every status change or significant action appends an `order_notes` record:

```php
OrderNote::create([
    'order_id' => $order->id,
    'user_id'  => auth()->id(),   // null if system/agent action
    'status'   => 'approved',     // status at time of note
    'note'     => "Approved by Jane Doe (digital signature).",
]);
```

The `notes()` relationship returns notes `orderBy('created_at', 'desc')`.

---

## Role-Based Data Access

| Role | Orders Visible | Actions |
|---|---|---|
| **Super Admin** | All orders, all statuses | All |
| **Store Manager** | Own region; statuses: `for approval`, `approved`, `rejected` | Submit, edit, cancel |
| **Approver** | Region's orders awaiting approval | Approve, reject |
| **Warehouse Manager** | Assigned warehouse; statuses: `completed`, `approved` | Complete |
| **Warehouse Personnel** | Same as manager | View only |

Filtering is applied in `OrderController::index()` by checking `auth()->user()->role` and `auth()->user()->location`.

---

## Order Approval Routing

```
order.requesting_store (e.g., "6009")
  → LocationConfig::regionForStore("6009")       → "stc"
  → LocationConfig::regionApproverUserId("stc")  → 42
  → User::find(42)                               → Approver
```

Approver is stored as a sentinel row in `settings_region_emails`:
- `email = '__approver__'`
- `label = user_id` (as string)

Notification emails for a store are fetched via `SettingsRegionEmail::emailsForStore($storeCode)`.

---

## Product Schemes (Freebies & Discounts)

Each product in `products_{storeCode}` has three scheme columns:

| Column | Type | Example | Meaning |
|---|---|---|---|
| `cash_bank_card_scheme` | string(10) | `"12+1"` | Buy 12 cases, get 1 freebie case |
| `po15_scheme` | string(10) | `"6+1"` | Buy 6 cases with PO15%, get 1 freebie |
| `discount_scheme` | string(10) | `"15"` or `"10%"` | Fixed amount or percentage discount |

**Payment mode → scheme selection (in AtomController):**
- `mode_payment` contains "Cash", "Bank", or "Card" → use `cash_bank_card_scheme`
- Everything else (PO, etc.) → use `po15_scheme` (default)

**Scheme parsing (`parseScheme("12+1")`):**
```php
['buy' => 12, 'free' => 1]
// Qualification: qty_per_cs >= buy → create FREEBIE line item
```

**Freebie item fields:**
- `item_type = 'FREEBIE'`
- `sku` = `product.freebie_sku` (may differ from main SKU)
- `total_qty` = number of freebie cases earned
- `freebies_per_cs` = same as total_qty
- `amount = 0`

---

## Two-Tier Inventory Allocation

Stock is tracked at two levels simultaneously:

### Tier 1 — Store Level (cases)

```
Table: products_{storeCode}  (e.g., products_6012)
Field: allocation_per_case   (integer, in cases)
```

- Decremented on order creation for **MAIN items only**
- Reverted on cancel/archive
- Re-deducted on restore

### Tier 2 — Warehouse Level (pieces)

```
Table: product_wms_allocations
Fields: sku, warehouse_code, wms_actual_allocation, wms_virtual_allocation
Key:   (sku, warehouse_code)  — composite primary key
```

- `wms_virtual_allocation` = pieces available (used by system for all deductions)
- `wms_actual_allocation` = real count from Oracle WMS (synced separately)
- Decremented for **MAIN + FREEBIE items**
- Piece count = `total_qty × qty_per_pc`

### Stock Operation Summary

| Event | allocation_per_case | wms_virtual_allocation |
|---|---|---|
| Order created | `-= qty_per_cs` (MAIN only) | `-= total_qty × qty_per_pc` (all) |
| Order updated | `±= diff_in_cs` (MAIN only) | `±= diff_in_pieces` (all) |
| Cancel / Archive | `+= total_qty` (MAIN only) | `+= total_qty × qty_per_pc` (all) |
| Restore | `-= qty_per_cs` (MAIN only) | `-= total_qty × qty_per_pc` (all) |

Both tiers use `GREATEST(0, value - deduction)` to prevent negative stock.

---

## SOF ID Formats

| Channel | Format | Example |
|---|---|---|
| Manual SOF | `SOF{YYYYMM}-{NNN}` | `SOF202506-001` |
| WooCommerce (Atom) | `ATOM{woo_order_no}-{YYYYMMDD}` | `ATOM12345-20260609` |

Next manual SOF ID is calculated in `SalesOrderController::create()` by querying the highest existing ID for the month.

---

## WooCommerce Order Flow (AtomController)

```
POST /api/atom-api/  (Bearer token)
  → normalizeOrderData()    field name normalization (qty→quantity, price→total)
  → processOrder()
      → check duplicate sof_id
      → extract store from meta_data._pickup_store
      → resolve warehouse via LocationConfig::warehouseForStore()
      → checkAllocationStock()  — both tiers, includes freebie requirements
      → create Order record
      → processOrderItem() for each line
          → get product from products_{storeCode}
          → get WMS allocation with row lock
          → calculate qty_cs, qty_pc, scheme
          → insert MAIN item
          → if scheme qualifies → insert FREEBIE item
          → if discount_scheme → insert DISCOUNT item
      → deductAllocationStock()  — both tiers atomically
  → return { internal_order_id, sof_id, items_count }
```

WooCommerce status mapping:
- `pending`, `processing` → `new order`
- `on-hold` → `pending`
- `completed` → `completed`
- `cancelled`, `failed` → `cancelled`

---

## Manual SOF Flow (SalesOrderController)

```
GET  /b2b2c/sales-order        → create form (next SOF ID pre-generated)
POST /b2b2c/sales-order        → store()
    → validate all fields
    → check duplicate SKUs in same order
    → validateOrderItem() per item
    → checkAllocationStock()
    → create Order + OrderItems
    → deductAllocationStock()
```

Differences from WooCommerce:
- SOF ID format: `SOF{YYYYMM}-{NNN}` (not ATOM)
- `channel_order` = the SOF ID itself (not "E-Commerce")
- Both MAIN and FREEBIE deduct `allocation_per_case` (AtomController skips freebies for Tier 1)

---

## Approval Modes

When `approveOrder()` is called, it accepts one of:

- **`scan`** — Approver uploads a signed physical document (PDF stored in `approval_document`)
- **`digital`** — System generates a signed PDF with embedded digital signature

The generated PDF is stored and the path saved to `orders.approval_document`.

---

## Regions & Store Hierarchy

From `LocationConfig` static fallback (actual data in `settings_*` tables):

| Region Key | Label | Stores | Warehouse |
|---|---|---|---|
| `lz` | Luzon | 6012 | 80141 |
| `stc` | South Cebu | 4002, 2008, 6009 | 80051 |
| `ntc` | North Cebu | 6010 | 80051 |
| `vs` | Non-Cebu (Visayas) | 2010, 2017, 2019, 3018, 3019 | 80191 |

Warehouse codes: `80141` (Silangan), `80001` (Central), `80041` (Procter), `80051` (Opao-ISO), `80071` (Big Blue), `80131` (Lower Tingub), `80181` (Bacolod Depot), `80191` (Tacloban Depot), `80211` (Sta. Rosa)

---

For API endpoint details, see [API-REFERENCE.md](API-REFERENCE.md)
For system architecture, see [ARCHITECTURE.md](ARCHITECTURE.md)
