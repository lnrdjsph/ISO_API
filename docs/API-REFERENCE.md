# API Reference

Complete endpoint reference for web and REST APIs.

## Web Routes (Session-Authenticated)

Base URL: `http://localhost:8000` (dev) or production domain  
Auth: Laravel session cookie (Fortify)

### Dashboard & Auth

```
GET  /                              Dashboard (redirects to /dashboard if logged out)
POST /login                         Login form submission
POST /logout                        Logout
GET  /check-session                 AJAX endpoint to check session validity (returns JSON)
```

### B2B Orders

```
GET  /b2b2c/orders                  List orders (filtered by role/location)
GET  /b2b2c/orders/create           Order creation form
POST /b2b2c/orders                  Store new order
GET  /b2b2c/orders/{id}             View order details
GET  /b2b2c/orders/{id}/edit        Edit order form
PUT  /b2b2c/orders/{id}             Update order
DELETE /b2b2c/orders/{id}           Delete order
POST /b2b2c/orders/{id}/approve     Approve order (status → "approved")
POST /b2b2c/orders/{id}/reject      Reject order (status → "rejected")
GET  /b2b2c/orders/{id}/print-sof   PDF: Statement of Facts
GET  /b2b2c/orders/{id}/print-invoice   PDF: Invoice
GET  /b2b2c/orders/{id}/print-freebies  PDF: Freebie details
GET  /b2b2c/orders/{id}/print-slip     PDF: Order slip
```

**Query filters (GET /b2b2c/orders):**
- `status` — Filter by order status (new, for_approval, approved, rejected, processing, completed)
- `store` — Filter by requesting store code
- `region` — Filter by region (approvers only)
- `date_from`, `date_to` — Date range filter
- `sort` — Sort field (created_at, sof_id, store, etc.)
- `per_page` — Pagination (default 15)

### B2B Forms (SOF/ROF Submission)

```
GET  /b2b2c/forms/create-sof        SOF (Statement of Facts) form
POST /b2b2c/forms/store-sof         Submit SOF
GET  /b2b2c/forms/create-rof        ROF form
POST /b2b2c/forms/store-rof         Submit ROF
```

### Products & Catalog

```
GET  /b2b2c/products                Product list (filtered by store)
GET  /b2b2c/products/create         Import/create form
POST /b2b2c/products                Store new product
GET  /b2b2c/products/{id}           View product details
POST /b2b2c/products/{id}/update    Update product pricing/allocation
GET  /b2b2c/products/export         Excel export of product catalog
POST /b2b2c/products/import         Bulk import from Excel
POST /b2b2c/products/fetch-allocations   Trigger WMS allocation fetch (background job)
```

### Sales Orders

```
GET  /b2b2c/sales-order/create      Sales order form
POST /b2b2c/sales-order             Store sales order
GET  /b2b2c/sales-order/list        View sales orders
```

### Reports

```
GET  /reports/sales                 Sales report (filtered by date, store, region)
GET  /reports/payments              Payment report
GET  /reports/orders                Order report (volume, status breakdown)
GET  /reports/freebies              Freebie distribution report
GET  /reports/export-excel          Export any report to Excel
```

### Settings

```
GET  /settings                      Settings dashboard
GET  /settings/stores               Store master data
POST /settings/stores               Create/update store
GET  /settings/warehouses           Warehouse master data
POST /settings/warehouses           Create/update warehouse
GET  /settings/regions              Region master data
POST /settings/regions              Create/update region
GET  /settings/approvers            Regional approver assignment
POST /settings/approvers            Assign approver to region
```

### User Management

```
GET  /users                         User list
GET  /users/create                  User creation form
POST /users                         Create user
GET  /users/{id}                    View user details
POST /users/{id}/update             Update user role/location
DELETE /users/{id}                  Delete user
GET  /users/{id}/switch-context     Switch user's active role/location (manager only)
```

### Activity Logs

```
GET  /logs                          Activity log viewer (date, user, action, description)
GET  /logs/export                   Export activity log to Excel
```

### Account Management

```
GET  /account                       Current user profile
POST /account/update-profile        Update name/email
POST /account/update-password       Change password
```

---

## REST API Routes (Token-Authenticated or Public)

Base URL: `http://localhost:8000/api` or production domain

### WooCommerce Webhook (Atom API)

**Authentication:** Bearer token (set in `.env` as `ATOM_API_TOKEN`)

```
POST /api/atom-api/test
     Description: Connectivity test (public, no auth)
     Response: { "status": "ok" }

POST /api/atom-api/
     Description: Receive WooCommerce order webhook
     Auth: Bearer {ATOM_API_TOKEN}
     Middleware: VerifyApiToken
     
     Request body:
     {
       "order_number": 12345,
       "_pickup_store": "LZ",
       "customer_name": "John Doe",
       "customer_phone": "09123456789",
       "items": [
         {
           "sku": "SKU001",
           "quantity": 10,
           "unit_price": 100.00
         }
       ],
       "payment_mode": "cash_bank_card_scheme"  // or "po15_scheme"
     }
     
     Response (201 Created):
     {
       "status": "success",
       "order_id": 999,
       "sof_id": "ATOM12345-20260609",
       "message": "Order received"
     }
     
     Error (409 Conflict - duplicate):
     {
       "status": "error",
       "message": "Duplicate order detected",
       "existing_order_id": 998
     }
     
     Error (400 Bad Request):
     {
       "status": "error",
       "message": "Stock unavailable",
       "sku": "SKU001",
       "requested": 10,
       "available": 5
     }
```

### iCard Loyalty API (ISO API Legacy)

**Authentication:** None (public endpoints, for internal use only — should restrict by IP in production)

```
POST /api/iso-api/test
     Description: Connectivity test
     Response: { "status": "ok" }

POST /api/iso-api/get-user-data
     Request: { "member_id": "123456" }
     Response: 
     {
       "member_id": "123456",
       "member_name": "John Doe",
       "tier": "Gold",
       "points_balance": 5000
     }

POST /api/iso-api/otp-send
     Request: { "phone": "09123456789" }
     Response: { "status": "sent", "expires_in": 180 }  // 3 min

POST /api/iso-api/otp-verify
     Request: { "phone": "09123456789", "otp": "123456" }
     Response: { "status": "verified", "token": "..." } or { "status": "invalid" }

POST /api/iso-api/loyalty-points
     Request: { "member_id": "123456" }
     Response: 
     {
       "member_id": "123456",
       "current_points": 5000,
       "lifetime_points": 50000,
       "tier": "Gold"
     }

POST /api/iso-api/transactions
     Request: { "member_id": "123456", "limit": 10 }
     Response:
     [
       {
         "transaction_id": "TXN001",
         "date": "2026-06-09",
         "amount": 1000,
         "points_earned": 100
       }
     ]
```

### Oracle RMS Integration

**Authentication:** None (public, for internal queries — should restrict by IP)

```
GET /api/oracle-rms/item
    Query params: 
    - sku (required)
    - store_code (optional, defaults to first location)
    
    Response:
    {
      "sku": "SKU001",
      "description": "Product Name",
      "unit_price": 100.00,
      "soh": 150,  // Stock on hand
      "allocated": 50,
      "available": 100
    }

POST /api/oracle-rms/search
    Request: { "query": "SKU001", "store_code": "LZ" }
    Response: [{ items matching query }]
```

### RMS Sync Endpoints

**Authentication:** None (public — internally triggered; should restrict by IP in production)

```
POST /api/v1/rms-sync/synchronize
     Description: Trigger RMS pricing/stock sync
     Request body: {} or { "store_code": "LZ" }
     Response (202 Accepted):
     {
       "status": "sync_started",
       "job_id": "job_abc123",
       "message": "RMS sync queued"
     }

GET /api/v1/rms-sync/status
    Query params: job_id (optional)
    Response:
    {
      "status": "in_progress",  // or "completed", "failed"
      "items_synced": 150,
      "timestamp": "2026-06-09T12:00:00Z"
    }

GET /api/v1/rms-sync/logs
    Query params: limit (default 20), offset (default 0)
    Response:
    [
      {
        "timestamp": "2026-06-09T12:00:00Z",
        "action": "sync",
        "status": "completed",
        "items_count": 150,
        "details": { ... }
      }
    ]
```

### Payment Processing

#### ECR Terminal API

**Authentication:** None (public — terminal endpoint)

```
POST /api/payment-data
     Description: Receive ECR terminal payment data
     Request body:
     {
       "terminal_id": "TERM001",
       "transaction_id": "TXN123",
       "amount": 1000.00,
       "timestamp": "2026-06-09T12:00:00Z",
       "card_last4": "1234"
     }
     Response: { "status": "received" }
```

#### MRC Tender API

**Authentication:** None (public — internal only)

```
POST /api/mrc/tender
     Description: MRC payment tender processing
     Request body:
     {
       "order_id": 999,
       "amount": 1000.00,
       "tender_type": "cash",  // or "card", "check"
       "reference_no": "REF123"
     }
     Response (200 OK):
     {
       "status": "processed",
       "tender_id": "TENDER123",
       "timestamp": "2026-06-09T12:00:00Z"
     }
```

### Order Transfer / BOL Lookup (Oracle WMS)

**Authentication:** None (public — internal only)

```
GET /api/order-status/{storeOrderNo}/{sku}
    Description: Look up order status from WMS
    Response:
    {
      "order_no": "ORD123",
      "sku": "SKU001",
      "quantity": 10,
      "status": "allocated",  // or "shipped", "delivered"
      "bol": "BOL123",
      "timestamp": "2026-06-09"
    }

GET /api/order-bol/{tsf}/{sku}
    Description: Get BOL for transfer
    Response:
    {
      "tsf": "TSF123",
      "sku": "SKU001",
      "bol": "BOL123",
      "quantity": 10,
      "origin": "WH001",
      "destination": "STORE001"
    }
```

---

## Common Request/Response Patterns

### Error Responses

**4xx (Client error):**
```json
{
  "status": "error",
  "message": "Human-readable error",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

**5xx (Server error):**
```json
{
  "status": "error",
  "message": "Server error occurred",
  "request_id": "req_abc123"
}
```

### Pagination (List Endpoints)

```json
{
  "data": [{ ...items }],
  "meta": {
    "total": 100,
    "per_page": 15,
    "current_page": 1,
    "last_page": 7
  }
}
```

### Timestamps

All timestamps in responses use ISO 8601 format (UTC): `2026-06-09T12:00:00Z`

---

## Rate Limiting

No explicit rate limiting implemented. Add middleware if needed for production.

---

## CORS

CORS is not explicitly configured (same-origin only). To enable for external clients, configure in `config/cors.php` or add middleware.

---

## Authentication Methods

1. **Session (Web):** Fortify login → session cookie stored by browser
2. **API Token:** `Authorization: Bearer {token}` header (Sanctum, currently only used for Atom webhook)
3. **Public (No Auth):** RMS, iCard, MRC, ECR endpoints (should restrict by IP in production)

---

For more details on integrations and data flow, see [ARCHITECTURE.md](ARCHITECTURE.md)  
For business logic and order rules, see [BUSINESS-CONTEXT.md](BUSINESS-CONTEXT.md)
