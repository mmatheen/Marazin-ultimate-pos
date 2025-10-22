# 📋 SALE ORDER IMPLEMENTATION GUIDE

## 🎯 Overview
Your system now supports **Sale Orders** using existing `sales` table! No new tables needed.

---

## 🗄️ Database Structure

### Updated `sales` Table
```
✅ Existing fields:
- id, customer_id, location_id, user_id
- invoice_no, sales_date, subtotal, final_total
- payment_status, discount_type, discount_amount

✨ NEW Sale Order fields:
- transaction_type: 'invoice' or 'sale_order'
- order_number: SO-2025-0001
- sales_rep_id: Foreign key to sales_reps table
- order_date: Order placement date
- expected_delivery_date: Expected delivery
- order_status: draft/pending/confirmed/processing/ready/delivered/completed/cancelled
- converted_to_sale_id: Links SO to final invoice
- order_notes: Customer instructions
```

---

## 🚀 Usage Examples

### 1️⃣ Create Sale Order

```php
use App\Models\Sale;
use App\Models\SalesProduct;

// Create Sale Order
$saleOrder = Sale::create([
    'transaction_type' => 'sale_order',
    'order_number' => Sale::generateOrderNumber($locationId),
    'customer_id' => $request->customer_id,
    'location_id' => $locationId,
    'user_id' => auth()->id(),
    'sales_rep_id' => $request->sales_rep_id, // From sales_reps table
    'order_date' => now(),
    'expected_delivery_date' => $request->delivery_date,
    'order_status' => 'pending',
    'sale_type' => 'Normal',
    'status' => 'draft',
    'subtotal' => $subtotal,
    'discount_type' => 'fixed',
    'discount_amount' => 0,
    'final_total' => $total,
    'payment_status' => 'Due', // No payment yet
    'total_paid' => 0,
    'order_notes' => $request->notes,
]);

// Add items
foreach ($request->items as $item) {
    SalesProduct::create([
        'sale_id' => $saleOrder->id,
        'product_id' => $item['product_id'],
        'batch_id' => $item['batch_id'],
        'location_id' => $locationId,
        'quantity' => $item['quantity'],
        'price' => $item['price'],
        'price_type' => 'retail',
        'tax' => $item['tax'],
    ]);
}
```

### 2️⃣ Get Pending Sale Orders

```php
// All pending orders
$pendingOrders = Sale::saleOrders()
    ->whereIn('order_status', ['pending', 'confirmed'])
    ->with(['customer', 'salesRep.user', 'products.product'])
    ->latest('order_date')
    ->get();

// By Sales Rep
$repOrders = Sale::saleOrders()
    ->bySalesRep($salesRepId)
    ->where('order_status', '!=', 'completed')
    ->get();

// By Date Range
$orders = Sale::saleOrders()
    ->whereBetween('order_date', [$startDate, $endDate])
    ->get();
```

### 3️⃣ Convert Sale Order to Invoice

```php
try {
    $saleOrder = Sale::findOrFail($id);
    
    // Convert to invoice (creates new sale record)
    $invoice = $saleOrder->convertToInvoice();
    
    // Now you have:
    // - Original Sale Order (status: completed)
    // - New Invoice (transaction_type: invoice)
    // - Stock is automatically reduced
    
    return response()->json([
        'message' => 'Sale Order converted to Invoice',
        'invoice_no' => $invoice->invoice_no,
        'invoice_id' => $invoice->id,
    ]);
    
} catch (\Exception $e) {
    return response()->json(['error' => $e->getMessage()], 400);
}
```

### 4️⃣ Update Order Status

```php
// Confirm order
$saleOrder->update(['order_status' => 'confirmed']);

// Mark as processing
$saleOrder->update(['order_status' => 'processing']);

// Ready for delivery
$saleOrder->update(['order_status' => 'ready']);

// Cancel order
$saleOrder->update(['order_status' => 'cancelled']);
```

### 5️⃣ Get Sales Rep Performance

```php
use App\Models\SalesRep;

$salesRep = SalesRep::with('user')->find($id);

// Get orders by this rep
$orders = Sale::saleOrders()
    ->bySalesRep($salesRep->id)
    ->whereBetween('order_date', [$startDate, $endDate])
    ->get();

// Get converted invoices by this rep
$invoices = Sale::invoices()
    ->bySalesRep($salesRep->id)
    ->whereBetween('sales_date', [$startDate, $endDate])
    ->get();

$stats = [
    'sales_rep' => $salesRep->user->name,
    'total_orders' => $orders->count(),
    'pending_orders' => $orders->where('order_status', 'pending')->count(),
    'completed_orders' => $orders->where('order_status', 'completed')->count(),
    'total_order_value' => $orders->sum('final_total'),
    'total_invoice_value' => $invoices->sum('final_total'),
    'conversion_rate' => $orders->count() > 0 
        ? ($orders->where('order_status', 'completed')->count() / $orders->count() * 100) 
        : 0,
];
```

---

## 🔄 Workflow Process

```
┌─────────────────┐
│  Sales Rep      │
│  Takes Order    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Create SO      │ ← transaction_type = 'sale_order'
│  (Draft/Pending)│   order_status = 'pending'
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Manager        │
│  Confirms       │ ← order_status = 'confirmed'
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Warehouse      │
│  Prepares       │ ← order_status = 'processing'
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Ready for      │
│  Delivery       │ ← order_status = 'ready'
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Deliver &      │
│  Convert to     │ ← convertToInvoice()
│  Invoice        │   Creates new record: transaction_type = 'invoice'
└────────┬────────┘   Stock reduced
         │
         ▼
┌─────────────────┐
│  Payment        │
│  Collection     │ ← payment_status updated
└─────────────────┘
```

---

## 📊 Reports & Queries

### Sales Rep Dashboard
```php
// Get today's orders by sales rep
$todayOrders = Sale::saleOrders()
    ->bySalesRep($salesRepId)
    ->whereDate('order_date', today())
    ->with('customer')
    ->get();

// Get this month's performance
$monthlyStats = Sale::invoices()
    ->bySalesRep($salesRepId)
    ->whereMonth('sales_date', now()->month)
    ->selectRaw('
        COUNT(*) as total_sales,
        SUM(final_total) as total_revenue,
        AVG(final_total) as avg_order_value
    ')
    ->first();
```

### Pending Orders Report
```php
$pendingReport = Sale::saleOrders()
    ->whereIn('order_status', ['pending', 'confirmed', 'processing'])
    ->with(['customer', 'salesRep.user'])
    ->get()
    ->groupBy('order_status');
```

### Conversion Report
```php
// Orders vs Invoices
$comparison = [
    'total_orders' => Sale::saleOrders()->count(),
    'completed_orders' => Sale::saleOrders()
        ->where('order_status', 'completed')->count(),
    'total_invoices' => Sale::invoices()->count(),
    'pending_orders' => Sale::saleOrders()
        ->whereIn('order_status', ['pending', 'confirmed'])->count(),
];
```

---

## 🎨 Blade View Examples

### Sale Order List
```blade
<table class="table">
    <thead>
        <tr>
            <th>Order No</th>
            <th>Customer</th>
            <th>Sales Rep</th>
            <th>Date</th>
            <th>Status</th>
            <th>Total</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($saleOrders as $order)
        <tr>
            <td>{{ $order->order_number }}</td>
            <td>{{ $order->customer->first_name }}</td>
            <td>{{ $order->salesRep->user->name ?? 'N/A' }}</td>
            <td>{{ $order->order_date->format('d/m/Y') }}</td>
            <td>
                <span class="badge badge-{{ $order->order_status == 'pending' ? 'warning' : 'info' }}">
                    {{ ucfirst($order->order_status) }}
                </span>
            </td>
            <td>₹{{ number_format($order->final_total, 2) }}</td>
            <td>
                @if($order->order_status != 'completed')
                    <button onclick="convertToInvoice({{ $order->id }})" 
                            class="btn btn-sm btn-success">
                        Convert to Invoice
                    </button>
                @else
                    <a href="{{ route('sales.show', $order->converted_to_sale_id) }}" 
                       class="btn btn-sm btn-primary">
                        View Invoice
                    </a>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
```

### JavaScript for Conversion
```javascript
function convertToInvoice(orderId) {
    if (!confirm('Convert this Sale Order to Invoice?')) return;
    
    axios.post(`/sale-orders/${orderId}/convert`)
        .then(response => {
            alert('✅ Converted to Invoice: ' + response.data.invoice_no);
            window.location.href = `/sales/${response.data.invoice_id}`;
        })
        .catch(error => {
            alert('❌ Error: ' + error.response.data.message);
        });
}
```

---

## 🔐 Controller Example

```php
<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SalesRep;
use Illuminate\Http\Request;

class SaleOrderController extends Controller
{
    public function index()
    {
        $saleOrders = Sale::saleOrders()
            ->with(['customer', 'salesRep.user', 'location'])
            ->latest('order_date')
            ->paginate(20);
            
        return view('sale-orders.index', compact('saleOrders'));
    }
    
    public function create()
    {
        $salesReps = SalesRep::active()
            ->with('user')
            ->get();
            
        return view('sale-orders.create', compact('salesReps'));
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'sales_rep_id' => 'required|exists:sales_reps,id',
            'expected_delivery_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
        ]);
        
        // Create sale order logic here
        // (Use example from section 1 above)
        
        return redirect()->route('sale-orders.index')
            ->with('success', 'Sale Order created successfully');
    }
    
    public function convertToInvoice($id)
    {
        try {
            $saleOrder = Sale::findOrFail($id);
            $invoice = $saleOrder->convertToInvoice();
            
            return response()->json([
                'success' => true,
                'message' => 'Sale Order converted to Invoice',
                'invoice_no' => $invoice->invoice_no,
                'invoice_id' => $invoice->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
```

---

## ✅ Migration Steps

1. **Backup database first!**
   ```bash
   mysqldump -u root -p your_database > backup_$(date +%Y%m%d).sql
   ```

2. **Run the migration:**
   ```bash
   php artisan migrate
   ```

3. **Test with sample data:**
   ```php
   // Create a test sale order
   $so = Sale::create([
       'transaction_type' => 'sale_order',
       'order_number' => Sale::generateOrderNumber(1),
       'customer_id' => 1,
       'location_id' => 1,
       'user_id' => 1,
       'sales_rep_id' => 1,
       'order_date' => now(),
       'order_status' => 'pending',
       'sale_type' => 'Normal',
       'status' => 'draft',
       'subtotal' => 1000,
       'final_total' => 1000,
       'payment_status' => 'Due',
       'total_paid' => 0,
   ]);
   ```

4. **Verify queries work:**
   ```php
   Sale::saleOrders()->get();
   Sale::invoices()->get();
   ```

---

## 🎯 Key Benefits

✅ **Reuses existing tables** - No new tables needed!
✅ **Same items table** - sales_products works for both
✅ **Easy conversion** - One method to convert SO → Invoice
✅ **Sales rep tracking** - Links to existing sales_reps table
✅ **Better reporting** - All data in one place
✅ **Stock control** - Stock reduced only on conversion
✅ **Payment tracking** - Payment status separate for orders vs invoices

---

## 📞 Support

Need help? Check:
- `Sale` model methods: `convertToInvoice()`, `generateOrderNumber()`
- Scopes: `saleOrders()`, `invoices()`, `pending()`, `bySalesRep()`
- Relationships: `salesRep()`, `convertedSale()`, `originalSaleOrder()`

---

**Created:** October 22, 2025
**System:** Marazin Ultimate POS
