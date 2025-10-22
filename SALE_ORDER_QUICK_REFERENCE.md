# 🚀 SALE ORDER QUICK REFERENCE

## Essential Commands & Methods

### 📝 Creating Sale Orders

```php
// Generate order number
$orderNo = Sale::generateOrderNumber($locationId);
// Returns: "AFS-SO-0001"

// Create sale order
$saleOrder = Sale::create([
    'transaction_type' => 'sale_order',
    'order_number' => $orderNo,
    'customer_id' => $customerId,
    'location_id' => $locationId,
    'user_id' => auth()->id(),
    'sales_rep_id' => $salesRepId,
    'order_date' => now(),
    'expected_delivery_date' => $deliveryDate,
    'order_status' => 'pending',
    'sale_type' => 'Normal',
    'status' => 'draft',
    'subtotal' => $subtotal,
    'final_total' => $total,
    'payment_status' => 'Due',
    'total_paid' => 0,
]);
```

---

### 🔍 Query Scopes

```php
// Get all sale orders
Sale::saleOrders()->get();

// Get all invoices
Sale::invoices()->get();

// Get pending sale orders
Sale::saleOrders()
    ->whereIn('order_status', ['pending', 'confirmed'])
    ->get();

// Get orders by sales rep
Sale::saleOrders()
    ->bySalesRep($salesRepId)
    ->get();

// Get today's orders
Sale::saleOrders()
    ->whereDate('order_date', today())
    ->get();

// Get orders in date range
Sale::saleOrders()
    ->whereBetween('order_date', [$start, $end])
    ->get();
```

---

### 🔄 Conversion

```php
// Convert sale order to invoice
$saleOrder = Sale::find($id);
$invoice = $saleOrder->convertToInvoice();

// Check conversion status
if ($saleOrder->order_status === 'completed') {
    $invoiceId = $saleOrder->converted_to_sale_id;
    $invoice = Sale::find($invoiceId);
}
```

---

### 📊 Relationships

```php
$sale = Sale::with(['customer', 'salesRep.user', 'products'])->find($id);

// Access relationships
$customerName = $sale->customer->first_name;
$repName = $sale->salesRep->user->name;
$items = $sale->products; // Collection of SalesProduct

// Converted invoice
if ($sale->isSaleOrder() && $sale->order_status === 'completed') {
    $invoice = $sale->convertedSale;
}

// Original sale order (if this is an invoice)
if ($sale->isInvoice()) {
    $originalOrder = $sale->originalSaleOrder;
}
```

---

### ✅ Status Updates

```php
// Update order status
$saleOrder->update(['order_status' => 'confirmed']);
$saleOrder->update(['order_status' => 'processing']);
$saleOrder->update(['order_status' => 'ready']);
$saleOrder->update(['order_status' => 'cancelled']);
```

---

### 📈 Reports & Stats

```php
// Sales rep daily report
$today = Sale::saleOrders()
    ->bySalesRep($salesRepId)
    ->whereDate('order_date', today())
    ->selectRaw('
        COUNT(*) as order_count,
        SUM(final_total) as total_value,
        COUNT(CASE WHEN order_status = "completed" THEN 1 END) as completed_count
    ')
    ->first();

// Monthly performance
$monthly = Sale::invoices()
    ->bySalesRep($salesRepId)
    ->whereMonth('sales_date', now()->month)
    ->sum('final_total');

// Pending orders value
$pendingValue = Sale::saleOrders()
    ->whereIn('order_status', ['pending', 'confirmed'])
    ->sum('final_total');

// Conversion rate
$totalOrders = Sale::saleOrders()->count();
$completedOrders = Sale::saleOrders()
    ->where('order_status', 'completed')
    ->count();
$conversionRate = ($completedOrders / $totalOrders) * 100;
```

---

### 🔢 Counts & Aggregates

```php
// Count by status
$statusCounts = Sale::saleOrders()
    ->selectRaw('order_status, COUNT(*) as count, SUM(final_total) as total')
    ->groupBy('order_status')
    ->get();

// Sales rep leaderboard
$leaderboard = SalesRep::with('user')
    ->withCount(['sales as order_count' => function ($query) {
        $query->where('transaction_type', 'sale_order');
    }])
    ->withSum(['sales as order_value' => function ($query) {
        $query->where('transaction_type', 'sale_order');
    }], 'final_total')
    ->orderBy('order_value', 'desc')
    ->get();
```

---

### 🎨 Blade Template Helpers

```php
// Check type
@if($sale->isSaleOrder())
    <span class="badge badge-info">Sale Order</span>
@else
    <span class="badge badge-success">Invoice</span>
@endif

// Status badge
@switch($sale->order_status)
    @case('pending')
        <span class="badge badge-warning">Pending</span>
        @break
    @case('confirmed')
        <span class="badge badge-info">Confirmed</span>
        @break
    @case('completed')
        <span class="badge badge-success">Completed</span>
        @break
    @case('cancelled')
        <span class="badge badge-danger">Cancelled</span>
        @break
@endswitch

// Show relevant number
{{ $sale->isInvoice() ? $sale->invoice_no : $sale->order_number }}

// Show sales rep
{{ $sale->salesRep?->user?->name ?? 'N/A' }}
```

---

### 🔐 Validation Rules

```php
// Store sale order
$request->validate([
    'customer_id' => 'required|exists:customers,id',
    'sales_rep_id' => 'required|exists:sales_reps,id',
    'location_id' => 'required|exists:locations,id',
    'expected_delivery_date' => 'required|date|after_or_equal:today',
    'items' => 'required|array|min:1',
    'items.*.product_id' => 'required|exists:products,id',
    'items.*.quantity' => 'required|numeric|min:0.01',
    'items.*.price' => 'required|numeric|min:0',
]);

// Convert to invoice
$request->validate([
    'sale_order_id' => 'required|exists:sales,id',
]);
```

---

### 🛡️ Authorization Checks

```php
// Can user convert order?
Gate::define('convert-sale-order', function ($user, $saleOrder) {
    return $user->hasRole('manager') && 
           $saleOrder->order_status === 'ready';
});

// Check in controller
if (Gate::denies('convert-sale-order', $saleOrder)) {
    abort(403, 'Not authorized to convert this order');
}

// Blade directive
@can('convert-sale-order', $saleOrder)
    <button>Convert to Invoice</button>
@endcan
```

---

### 📱 API Endpoints

```php
// routes/api.php
Route::prefix('sale-orders')->group(function () {
    Route::get('/', [SaleOrderController::class, 'index']);
    Route::post('/', [SaleOrderController::class, 'store']);
    Route::get('/{id}', [SaleOrderController::class, 'show']);
    Route::patch('/{id}', [SaleOrderController::class, 'update']);
    Route::post('/{id}/convert', [SaleOrderController::class, 'convert']);
    Route::get('/sales-rep/{repId}', [SaleOrderController::class, 'byRep']);
});
```

---

### 🧪 Testing Examples

```php
// Test sale order creation
public function test_can_create_sale_order()
{
    $data = [
        'customer_id' => 1,
        'sales_rep_id' => 1,
        'location_id' => 1,
        'expected_delivery_date' => now()->addDays(3),
        'items' => [
            ['product_id' => 1, 'quantity' => 5, 'price' => 100]
        ]
    ];
    
    $response = $this->post('/sale-orders', $data);
    
    $response->assertStatus(201);
    $this->assertDatabaseHas('sales', [
        'transaction_type' => 'sale_order',
        'customer_id' => 1,
    ]);
}

// Test conversion
public function test_can_convert_sale_order_to_invoice()
{
    $saleOrder = Sale::factory()->saleOrder()->create();
    
    $invoice = $saleOrder->convertToInvoice();
    
    $this->assertEquals('invoice', $invoice->transaction_type);
    $this->assertEquals('completed', $saleOrder->fresh()->order_status);
    $this->assertEquals($invoice->id, $saleOrder->converted_to_sale_id);
}
```

---

### 🔧 Useful Helpers

```php
// Get all active sales reps
$salesReps = SalesRep::active()
    ->with('user')
    ->get()
    ->pluck('user.name', 'id');

// Get pending orders count
$pendingCount = Sale::saleOrders()
    ->where('order_status', 'pending')
    ->count();

// Get orders awaiting conversion
$readyOrders = Sale::saleOrders()
    ->where('order_status', 'ready')
    ->count();

// Check if order can be converted
function canConvert($saleOrder) {
    return $saleOrder->isSaleOrder() && 
           $saleOrder->order_status !== 'completed' &&
           $saleOrder->order_status !== 'cancelled';
}
```

---

### 💡 Pro Tips

```php
// Always eager load relationships to avoid N+1
Sale::saleOrders()
    ->with(['customer', 'salesRep.user', 'products.product'])
    ->get();

// Use transactions for conversion
DB::transaction(function () use ($saleOrder) {
    $invoice = $saleOrder->convertToInvoice();
    // Other operations...
});

// Cache frequently accessed data
Cache::remember('pending-orders-count', 60, function () {
    return Sale::saleOrders()
        ->where('order_status', 'pending')
        ->count();
});

// Use query scopes for cleaner code
// Instead of:
Sale::where('transaction_type', 'sale_order')
    ->where('sales_rep_id', $id)
    ->get();

// Use:
Sale::saleOrders()->bySalesRep($id)->get();
```

---

## 📋 Status Workflow Cheat Sheet

```
draft → pending → confirmed → processing → ready → [CONVERT] → completed
                                                                    ↓
                                                            Invoice Created
                                                            Stock Reduced
                                                            Ready for Payment

cancelled ← (can cancel at any point before conversion)
```

---

## 🎯 Common Patterns

### Pattern 1: Sales Rep Daily Workflow
```php
// Morning: Get today's pending orders
$myOrders = Sale::saleOrders()
    ->bySalesRep(auth()->user()->salesRep->id)
    ->whereDate('order_date', today())
    ->get();

// Create new order
$newOrder = Sale::create([...]);

// End of day: Check completion
$completed = $myOrders->where('order_status', 'completed')->count();
```

### Pattern 2: Manager Approval
```php
// Get pending approvals
$pending = Sale::saleOrders()
    ->where('order_status', 'pending')
    ->with(['customer', 'salesRep.user'])
    ->get();

// Approve order
$order->update(['order_status' => 'confirmed']);
```

### Pattern 3: Warehouse Processing
```php
// Get confirmed orders
$toProcess = Sale::saleOrders()
    ->where('order_status', 'confirmed')
    ->with('products.product')
    ->get();

// Mark as processing
$order->update(['order_status' => 'processing']);

// When ready
$order->update(['order_status' => 'ready']);
```

---

**Last Updated:** October 22, 2025  
**Version:** 1.0  
**System:** Marazin Ultimate POS
