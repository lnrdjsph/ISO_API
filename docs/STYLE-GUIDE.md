# Style Guide

Code conventions and architectural patterns used in this codebase.

## Laravel Version & PHP

- **Laravel 9** — Follow Laravel 9 conventions (Eloquent, middleware, services)
- **PHP 8.0+** — Use modern PHP features (typed properties, nullsafe operator, match expressions)

## Naming Conventions

### Models

- **Singular, PascalCase:** `Order`, `OrderItem`, `ActivityLog`
- **Namespace hierarchy:** `app/Models/ISO_B2B/Order.php` for order-related models
- **Relationship names:** Singular for `belongsTo`, plural for `hasMany`

```php
// app/Models/ISO_B2B/Order.php
class Order extends Model {
    public function items() {
        return $this->hasMany(OrderItem::class);
    }
    
    public function approver() {
        return $this->belongsTo(User::class, 'approver_user_id');
    }
}
```

### Controllers

- **PascalCase + `Controller` suffix:** `OrderController`, `ProductController`
- **Action methods as verbs:** `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`
- **Custom methods:** Domain-specific verbs: `approve`, `reject`, `export`, `import`

```php
class OrderController extends Controller {
    public function index() { }           // List
    public function show($id) { }         // View
    public function create() { }          // Form
    public function store(Request $r) { } // Save
    public function approve($id) { }      // Custom: approve
    public function exportSof($id) { }    // Custom: export SOF
}
```

### Services

- **PascalCase + `Service` suffix:** `OtpService`, `MRCTenderService`
- **Location:** `app/Services/`
- **Public methods:** Action verbs (imperative): `generate()`, `verify()`, `process()`

```php
class OtpService {
    public function generate(string $phone): string { }
    public function verify(string $phone, string $otp): bool { }
}
```

### Database

- **Tables:** Plural, snake_case: `orders`, `order_items`, `activity_logs`
- **Columns:** snake_case: `requesting_store`, `created_at`, `updated_at`
- **Foreign keys:** `{model}_id` (singular): `order_id`, `user_id`, `approver_user_id`
- **Status columns:** `status` (values in lowercase)
- **Dynamic tables:** `products_{store_code}` (lowercase store code)

### Routes

- **Web routes:** Plural resource paths: `/b2b2c/orders`, `/b2b2c/products`
- **API routes:** Namespaced: `/api/atom-api/`, `/api/v1/rms-sync/`
- **Route names:** snake_case: `orders.index`, `orders.show`, `orders.approve`

```php
// routes/web.php
Route::resource('b2b2c.orders', OrderController::class);
Route::post('b2b2c/orders/{id}/approve', [OrderController::class, 'approve'])->name('orders.approve');

// routes/api.php
Route::post('api/atom-api/', [AtomController::class, 'receiveOrder']);
Route::post('api/v1/rms-sync/synchronize', [RMSCommerceSynchronizationController::class, 'synchronize']);
```

### Methods & Variables

- **Variables:** camelCase: `$requestingStore`, `$orderItems`, `$approverUserId`
- **Constants:** UPPER_SNAKE_CASE: `const ORDER_STATUS_APPROVED = 'approved'`
- **Booleans:** Prefix with `is`, `has`, `should`: `$isApproved`, `$hasItems`, `$shouldNotify`

```php
$isApproved = $order->status === 'approved';
$hasAllocations = $allocation > 0;
$shouldDeductStock = true;
```

## Architecture Patterns

### Repository Pattern (Conditional)

For complex queries, use **query classes** or **service methods** (not traditional repositories):

```php
// DON'T create a full Repository class
// DO: Put complex query logic in Service or Model scope

// app/Models/Order.php
class Order {
    public function scopeApprovedByRegion($query, $region) {
        return $query->where('approval_status', 'approved')
                     ->whereIn('requesting_store', LocationConfig::regionStores($region));
    }
}

// Usage in controller
$orders = Order::approvedByRegion('MANILA')->get();
```

### Service Layer

Complex business logic goes in Services:

```php
// app/Services/RMSCommerceSynchronizationService.php
class RMSCommerceSynchronizationService {
    public function synchronize(): void {
        $items = $this->fetchRmsItems();
        $csv = $this->generateCsv($items);
        $this->uploadViaSftp($csv);
        $this->logSync();
    }
}

// app/Http/Controllers/RMSCommerceSynchronizationController.php
class RMSCommerceSynchronizationController {
    public function synchronize(RMSCommerceSynchronizationService $service) {
        RMSSynchronizationJob::dispatch();
        return response()->json(['status' => 'queued']);
    }
}
```

### Trait Pattern (for Cross-Cutting Concerns)

Use traits for shared behavior across multiple classes:

```php
// app/Traits/LogsActivity.php
trait LogsActivity {
    protected function logActivity($action, $description, $properties = []) {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'properties' => json_encode($properties),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}

// Usage in controller
class OrderController extends Controller {
    use LogsActivity;
    
    public function approve($id) {
        $order = Order::find($id);
        $order->update(['status' => 'approved']);
        $this->logActivity('approve', "Approved order {$id}", ['order_id' => $id]);
    }
}
```

### Middleware for Cross-Cutting Concerns

Use middleware for request filtering, auth, security:

```php
// app/Http/Middleware/VerifyApiToken.php
class VerifyApiToken {
    public function handle($request, $next) {
        $token = $request->bearerToken();
        if ($token !== config('services.atom.api_token')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        return $next($request);
    }
}

// routes/api.php
Route::post('api/atom-api/', [AtomController::class, 'receiveOrder'])
    ->middleware('verify.api.token');
```

### Helper Classes (for Utilities)

Stateless utility classes for cross-cutting calculations:

```php
// app/Helpers/ISO8583Client.php
class ISO8583Client {
    public static function buildMessage($amount, $stan, $pan) { }
    public static function parseResponse($rawResponse) { }
}

// Usage
$response = ISO8583Client::parseResponse($hostResponse);
```

### Config-Driven Behavior

Use `config/` files for environment-dependent behavior:

```php
// config/services.php
return [
    'atom' => [
        'api_token' => env('ATOM_API_TOKEN'),
    ],
    'jpos' => [
        'host' => env('JPOS_HOST', 'localhost'),
        'port' => env('JPOS_PORT', 9999),
    ],
    'sftp' => [
        'host' => env('SFTP_HOST'),
        'user' => env('SFTP_USER'),
        'pass' => env('SFTP_PASS'),
    ],
];

// Usage in controller/service
$host = config('services.jpos.host');
```

### Caching for Performance

Cache expensive queries with strategic TTLs:

```php
// app/Support/LocationConfig.php
class LocationConfig {
    protected const CACHE_TTL = 86400; // 24 hours
    
    public static function regions() {
        return Cache::remember('location:regions', self::CACHE_TTL, function() {
            return SettingsRegion::all()->keyBy('region_key');
        });
    }
}

// Invalidate on write
// app/Http/Controllers/SettingsController.php
public function updateRegion($id, Request $request) {
    // Update...
    Cache::forget('location:regions');
}
```

## Code Style

### Eloquent Models

```php
class Order extends Model {
    use HasFactory;
    
    protected $fillable = [
        'sof_id',
        'requesting_store',
        'approval_status',
        'created_at',
    ];
    
    protected $casts = [
        'order_date' => 'datetime',
        'items_count' => 'integer',
    ];
    
    // Relationships first
    public function items() {
        return $this->hasMany(OrderItem::class);
    }
    
    public function approver() {
        return $this->belongsTo(User::class, 'approver_user_id');
    }
    
    // Scopes next
    public function scopeApproved($query) {
        return $query->where('status', 'approved');
    }
    
    // Accessors/mutators after
    // Custom methods last
    public function totalAmount() {
        return $this->items->sum('subtotal');
    }
}
```

### Controllers

```php
class OrderController extends Controller {
    public function __construct() {
        $this->middleware('auth');
    }
    
    // List with filtering
    public function index(Request $request) {
        $orders = Order::query();
        
        // Apply filters
        if ($request->filled('status')) {
            $orders->where('status', $request->status);
        }
        
        // Pagination
        return response()->json($orders->paginate(15));
    }
    
    // Create
    public function store(Request $request) {
        $validated = $request->validate([
            'requesting_store' => 'required|exists:settings_stores,code',
            'items' => 'required|array|min:1',
        ]);
        
        $order = Order::create($validated);
        
        $this->logActivity('create', "Created order {$order->id}");
        
        return response()->json($order, 201);
    }
    
    // Custom action
    public function approve($id) {
        $order = Order::findOrFail($id);
        
        if ($order->status !== 'for_approval') {
            return response()->json(['error' => 'Invalid status'], 422);
        }
        
        $order->update(['status' => 'approved']);
        $this->logActivity('approve', "Approved order {$id}");
        
        return response()->json($order);
    }
}
```

### Validation

Use Form Request classes for complex validations:

```php
// app/Http/Requests/StoreOrderRequest.php
class StoreOrderRequest extends FormRequest {
    public function authorize() {
        return auth()->check();
    }
    
    public function rules() {
        return [
            'requesting_store' => 'required|string|exists:settings_stores,store_code',
            'items' => 'required|array|min:1',
            'items.*.sku' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_mode' => 'required|in:po15_scheme,cash_bank_card_scheme',
        ];
    }
}

// Usage in controller
public function store(StoreOrderRequest $request) {
    $validated = $request->validated();
    // ...
}
```

### Error Handling

Use try/catch sparingly; let exceptions bubble for middleware handling:

```php
// DO: Let exceptions bubble to exception handler
public function store(Request $request) {
    Order::create($request->validated()); // May throw ModelNotFoundException
}

// DON'T: Over-catch exceptions
public function store(Request $request) {
    try {
        Order::create($request->validated());
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed'], 500);
    }
}

// DO: Catch specific exceptions when needed
public function store(Request $request) {
    try {
        Order::create($request->validated());
    } catch (QueryException $e) {
        // Handle database constraint violations
        return response()->json(['error' => 'Duplicate order'], 409);
    }
}
```

### Comments

Write comments only for **why**, not **what** (code already shows what):

```php
// DON'T: Comments that just repeat the code
public function approve($id) {
    // Find the order
    $order = Order::find($id);
    
    // Update status to approved
    $order->update(['status' => 'approved']);
}

// DO: Comments that explain the why or non-obvious logic
public function approve($id) {
    $order = Order::find($id);
    
    // Prevent re-approval of already processed orders
    if ($order->status !== 'for_approval') {
        abort(422);
    }
    
    $order->update(['status' => 'approved']);
}
```

## Performance Patterns

### N+1 Queries

Always eager-load relationships:

```php
// DON'T: N+1 query problem
$orders = Order::all();
foreach ($orders as $order) {
    echo $order->approver->name; // Query per order
}

// DO: Eager-load relationships
$orders = Order::with('approver')->get();
foreach ($orders as $order) {
    echo $order->approver->name; // No additional queries
}
```

### Database Indexes

Add indexes for frequently queried columns:

```php
// app/Models/Order.php
protected $indexable = [
    'requesting_store',
    'approval_status',
    'created_at',
    'sof_id', // Unique
];

// Migration
Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->string('requesting_store')->index();
    $table->enum('status', ['new', 'for_approval', 'approved', 'rejected', 'processing', 'completed'])->index();
    $table->unique('sof_id');
});
```

### Queued Jobs for Heavy Operations

Move heavy processing to background jobs:

```php
// app/Jobs/RMSSynchronizationJob.php
class RMSSynchronizationJob implements ShouldQueue {
    public function handle() {
        // Fetch RMS data
        // Generate CSV
        // Upload via SFTP
    }
}

// Controller dispatches async
public function synchronize(Request $request) {
    RMSSynchronizationJob::dispatch();
    return response()->json(['status' => 'queued']);
}
```

## Frontend (Blade + Vite)

### Template Organization

```
resources/views/
├── layouts/
│   ├── app.blade.php         # Main layout
│   └── guest.blade.php       # Login layout
├── b2b2c/
│   ├── orders/
│   │   ├── index.blade.php   # List
│   │   ├── create.blade.php  # Form
│   │   └── show.blade.php    # Detail
│   └── products/
│       ├── index.blade.php
│       └── import.blade.php
├── components/
│   ├── order-card.blade.php
│   └── pagination.blade.php
└── errors/
    ├── 404.blade.php
    └── 500.blade.php
```

### CSS (Tailwind)

Use Tailwind utilities; avoid custom CSS unless necessary:

```html
<!-- Good: Tailwind utilities -->
<div class="bg-blue-500 text-white p-4 rounded-lg shadow-lg">
    Order Approved
</div>

<!-- Avoid: Custom CSS for simple styling -->
<div class="custom-approval-banner">Order Approved</div>
```

### JavaScript (Vite Assets)

Keep JS modular using ES modules:

```javascript
// resources/js/modules/order-form.js
export function initOrderForm(formSelector) {
    const form = document.querySelector(formSelector);
    form.addEventListener('submit', handleSubmit);
}

// resources/js/app.js
import { initOrderForm } from './modules/order-form.js';
initOrderForm('#order-form');
```

---

For architectural decisions, see [ARCHITECTURE.md](ARCHITECTURE.md)  
For business rules and domain terminology, see [BUSINESS-CONTEXT.md](BUSINESS-CONTEXT.md)
