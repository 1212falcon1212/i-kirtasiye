---
name: laravel-api
description: Laravel API development for Controllers, Services, Models, and Sanctum auth
user-invocable: true
---

# Laravel API Module

> Backend development with Laravel, Eloquent, and REST API patterns

**Activates on:** API, endpoint, route, controller, service, model, migration, Laravel, backend

**Collaborates with:** `ui` for frontend integration, `debug` for testing

---

## Project Structure

```
backend/
├── app/
│   ├── Http/Controllers/Api/    # API controllers
│   ├── Models/                  # Eloquent models
│   ├── Services/                # Business logic layer
│   ├── Observers/               # Model observers
│   ├── Jobs/                    # Queue jobs
│   └── Filament/                # Admin panel
├── routes/api.php               # API routes (Sanctum protected)
├── database/migrations/         # Schema migrations
└── config/                      # Configuration
```

---

## Controller Pattern

Every API controller follows this structure:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    // STEP 1: Auth is handled by Sanctum middleware in routes/api.php
    // STEP 2: Validate input
    // STEP 3: Authorization (ownership/role check)
    // STEP 4: Execute with error handling

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $orders = Order::forUser($user->id)
            ->with(['items.product', 'items.seller'])
            ->latest()
            ->paginate(20);

        return response()->json($orders);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'field' => 'required|string|max:255',
        ]);

        try {
            $result = $this->service->create($validated);
            return response()->json([
                'message' => 'Basariyla olusturuldu.',
                'data' => $result,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Islem basarisiz.',
            ], 500);
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $resource = Model::find($id);

        if (!$resource) {
            return response()->json(['message' => 'Bulunamadi.'], 404);
        }

        // Ownership check
        if ($resource->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Yetkiniz yok.'], 403);
        }

        return response()->json($resource);
    }
}
```

---

## Service Layer Pattern

```php
<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function create(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $order = Order::create($data);

            foreach ($data['items'] as $item) {
                $order->items()->create($item);
            }

            return $order->load('items');
        });
    }
}
```

---

## Model Pattern

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'status', 'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'shipped_at' => 'datetime',
        ];
    }

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // Scopes
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
```

---

## Route Pattern

```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'store']);
        Route::get('/{id}', [OrderController::class, 'show']);
        Route::put('/{id}/status', [OrderController::class, 'updateStatus']);
    });
});
```

---

## Migration Pattern

```php
Schema::create('table_name', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('status')->default('pending');
    $table->decimal('amount', 10, 2);
    $table->json('data')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();

    $table->index(['user_id', 'status', 'created_at']);
});
```

---

## Observer Pattern

```php
<?php

namespace App\Observers;

use App\Models\Offer;
use App\Jobs\NotifyPriceDropJob;
use Illuminate\Support\Facades\Cache;

class OfferObserver
{
    public function updated(Offer $offer): void
    {
        if ($offer->isDirty('price') && $offer->price < $offer->getOriginal('price')) {
            $oldPrice = (float) $offer->getOriginal('price');
            NotifyPriceDropJob::dispatch($offer, $oldPrice);
        }

        Cache::forget('cms.homepage.sections');
    }
}
```

---

## Settings Pattern (via Setting model)

```php
// Read
$value = Setting::getValue('group.key', 'default');

// Write
Setting::setValue('group.key', $value, 'group', 'string');

// Cache
Cache::remember('cache.key', 3600, fn() => Setting::getValue('key', 'default'));

// Invalidate
Cache::forget('cache.key');
```

---

## API Response Conventions

```php
// Success
return response()->json(['message' => 'Basarili.', 'data' => $result]);
return response()->json(['message' => 'Olusturuldu.', 'data' => $result], 201);

// Error
return response()->json(['message' => 'Bulunamadi.'], 404);
return response()->json(['message' => 'Yetkiniz yok.'], 403);
return response()->json(['message' => 'Gecersiz veri.', 'errors' => $errors], 422);

// Paginated
return response()->json(Model::paginate(20));
```

---

## Database Best Practices

```php
// ALWAYS: Eager load to prevent N+1
$orders = Order::with(['items.product', 'items.seller'])->get();

// ALWAYS: Paginate list queries
$items = Model::latest()->paginate(20);

// ALWAYS: Use transactions for multi-step writes
DB::transaction(function () {
    // multiple creates/updates
});

// ALWAYS: Use correct column types
// Money: decimal(10,2)
// IDs: foreignId (bigint unsigned)
// Status: string (not enum — easier to extend)
// JSON data: json nullable
```

---

## Checklist

```
[ ] Auth middleware on route
[ ] Request validation
[ ] Ownership/role check
[ ] Eager loading (no N+1)
[ ] Transactions for multi-writes
[ ] Cache invalidation if applicable
[ ] Turkish messages for user-facing responses
[ ] Decimal for money fields
[ ] Index on frequently queried columns
```

---

## Red Flags

| Pattern | Resolution |
|---------|------------|
| No `auth:sanctum` middleware | Add to route group |
| `Model::all()` without pagination | Use `paginate()` |
| N+1 queries (no `with()`) | Add eager loading |
| `$request->all()` | Use `$request->validate()` |
| Money as float | Use `decimal(10,2)` |
| Status as enum column | Use varchar string |
| No error handling on writes | Wrap in try/catch |
