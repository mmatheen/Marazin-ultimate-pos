# 🇮🇳 SALE ORDER செயல்படுத்தல் வழிகாட்டி (TAMIL)

## 📋 சுருக்கம்

உங்கள் system-ல் **Sale Order** support இப்போது உள்ளது! புதிய table-கள் எதுவும் வேண்டாம் - existing `sales` table-ஐ reuse செய்கிறோம்.

---

## 🎯 முக்கிய புள்ளிகள்

### ✅ உங்களிடம் ஏற்கனவே உள்ளவை:
1. **`sales` table** - Invoice மற்றும் sales data
2. **`sales_products` table** - Sale items
3. **`sales_reps` table** - Sales Representatives ✅
4. **`customers` table** - Customer information

### ✨ புதிதாக சேர்த்தவை:
`sales` table-ல் இந்த columns சேர்க்கப்பட்டுள்ளன:

```
transaction_type → 'invoice' or 'sale_order'
order_number → SO-2025-0001 (Sale Order number)
sales_rep_id → எந்த sales rep order எடுத்தார்
order_date → Order எடுத்த தேதி
expected_delivery_date → Delivery எப்போது
order_status → pending/confirmed/completed/cancelled
converted_to_sale_id → SO-ல் இருந்து Invoice link
order_notes → Customer instructions
```

---

## 🔄 எப்படி வேலை செய்யும்?

### 1️⃣ Sale Order உருவாக்குதல்

```php
// Sales Rep customer கிட்ட order வாங்குறார்
$saleOrder = Sale::create([
    'transaction_type' => 'sale_order',  // ⭐ முக்கியம்!
    'order_number' => 'SO-2025-0001',
    'customer_id' => $customerId,
    'sales_rep_id' => $salesRepId,      // எந்த rep
    'order_date' => இன்றைய_தேதி,
    'order_status' => 'pending',
    'final_total' => 5000,
    'payment_status' => 'Due',           // இன்னும் payment இல்லை
]);

// Items add செய்யுங்கள்
foreach ($items as $item) {
    SalesProduct::create([
        'sale_id' => $saleOrder->id,
        'product_id' => $item['product_id'],
        'quantity' => $item['qty'],
        'price' => $item['price'],
    ]);
}
```

### 2️⃣ Pending Orders பார்க்க

```php
// எல்லா pending orders
$pendingOrders = Sale::saleOrders()
    ->where('order_status', 'pending')
    ->with('customer', 'salesRep')
    ->get();

// குறிப்பிட்ட Sales Rep-ன் orders
$repOrders = Sale::saleOrders()
    ->bySalesRep($salesRepId)
    ->get();
```

### 3️⃣ Sale Order → Invoice மாற்றுதல்

```php
// Order confirm ஆனதும் Invoice ஆக்குங்கள்
$saleOrder = Sale::find($id);
$invoice = $saleOrder->convertToInvoice();

// இப்போது:
// ✅ Original Sale Order status = 'completed'
// ✅ புதிய Invoice உருவாக்கப்பட்டது
// ✅ Stock automatically குறைக்கப்பட்டது
// ✅ Payment collect பண்ணலாம்
```

---

## 📊 Data Examples

### Sale Order Entry:
```
ID: 1
transaction_type: 'sale_order'
order_number: 'SO-2025-0001'
invoice_no: NULL (இன்னும் invoice இல்லை)
order_status: 'pending'
payment_status: 'Due'
sales_rep_id: 5 (எந்த rep)
```

### After Conversion:
**Original Sale Order (ID: 1):**
```
order_status: 'completed' ✅
converted_to_sale_id: 2 (link to invoice)
```

**New Invoice (ID: 2):**
```
transaction_type: 'invoice'
invoice_no: 'INV-2025-0042' ✅
order_number: NULL
payment_status: 'Due' (payment collect பண்ணணும்)
sales_rep_id: 5 (same rep)
```

---

## 🎨 Status Flow

```
┌─────────────────┐
│  Sales Rep      │
│  Order எடுக்குது │
└────────┬────────┘
         │
         ▼
    order_status = 'pending'
    
         │
         ▼
┌─────────────────┐
│  Manager        │
│  Approve பண்ணுது │
└────────┬────────┘
         │
         ▼
    order_status = 'confirmed'
    
         │
         ▼
┌─────────────────┐
│  Warehouse      │
│  தயார் செய்யுது  │
└────────┬────────┘
         │
         ▼
    order_status = 'processing'
    
         │
         ▼
┌─────────────────┐
│  Delivery       │
│  Convert to     │
│  Invoice        │
└────────┬────────┘
         │
         ▼
    convertToInvoice() method call
    
         │
         ▼
┌─────────────────┐
│  Payment        │
│  வசூல் செய்யுது │
└─────────────────┘
```

---

## 💼 Sales Rep Performance

```php
// Sales Rep-ன் orders எல்லாம் பார்க்க
$salesRep = SalesRep::find($id);

$todayOrders = Sale::saleOrders()
    ->bySalesRep($salesRep->id)
    ->whereDate('order_date', today())
    ->get();

$monthlyStats = [
    'total_orders' => $todayOrders->count(),
    'total_value' => $todayOrders->sum('final_total'),
    'pending' => $todayOrders->where('order_status', 'pending')->count(),
    'completed' => $todayOrders->where('order_status', 'completed')->count(),
];

// Rep-ன் converted invoices (actual sales)
$invoices = Sale::invoices()
    ->bySalesRep($salesRep->id)
    ->whereMonth('sales_date', now()->month)
    ->get();

$revenue = $invoices->sum('final_total');
```

---

## 🔧 Controller Example (Tamil Comments)

```php
<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SalesRep;

class SaleOrderController extends Controller
{
    // Sale Orders list காட்டுது
    public function index()
    {
        $saleOrders = Sale::saleOrders()
            ->with(['customer', 'salesRep.user'])
            ->latest('order_date')
            ->paginate(20);
            
        return view('sale-orders.index', compact('saleOrders'));
    }
    
    // புதிய Sale Order create form
    public function create()
    {
        // Active sales reps மட்டும் கொடுக்கணும்
        $salesReps = SalesRep::active()
            ->with('user')
            ->get();
            
        return view('sale-orders.create', compact('salesReps'));
    }
    
    // Sale Order save பண்ணுது
    public function store(Request $request)
    {
        $saleOrder = Sale::create([
            'transaction_type' => 'sale_order',
            'order_number' => Sale::generateOrderNumber($request->location_id),
            'customer_id' => $request->customer_id,
            'sales_rep_id' => $request->sales_rep_id,
            'location_id' => $request->location_id,
            'user_id' => auth()->id(),
            'order_date' => now(),
            'expected_delivery_date' => $request->delivery_date,
            'order_status' => 'pending',
            'sale_type' => 'Normal',
            'status' => 'draft',
            'subtotal' => $request->subtotal,
            'final_total' => $request->total,
            'payment_status' => 'Due',
            'total_paid' => 0,
        ]);
        
        // Items add பண்ணுது
        foreach ($request->items as $item) {
            SalesProduct::create([
                'sale_id' => $saleOrder->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                // ... other fields
            ]);
        }
        
        return redirect()->route('sale-orders.index')
            ->with('success', 'Sale Order உருவாக்கப்பட்டது!');
    }
    
    // Sale Order-ஐ Invoice ஆக மாற்றுது
    public function convertToInvoice($id)
    {
        try {
            $saleOrder = Sale::findOrFail($id);
            
            // Magic method! 🎩✨
            $invoice = $saleOrder->convertToInvoice();
            
            return response()->json([
                'success' => true,
                'message' => 'Invoice உருவாக்கப்பட்டது!',
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

## 📱 Routes Setup

```php
// routes/web.php

Route::prefix('sale-orders')->group(function () {
    Route::get('/', [SaleOrderController::class, 'index'])
        ->name('sale-orders.index');
    
    Route::get('/create', [SaleOrderController::class, 'create'])
        ->name('sale-orders.create');
    
    Route::post('/', [SaleOrderController::class, 'store'])
        ->name('sale-orders.store');
    
    Route::post('/{id}/convert', [SaleOrderController::class, 'convertToInvoice'])
        ->name('sale-orders.convert');
        
    Route::patch('/{id}/status', [SaleOrderController::class, 'updateStatus'])
        ->name('sale-orders.update-status');
});
```

---

## ✅ செயல்படுத்துவது எப்படி?

### Step 1: Migration Run பண்ணுங்கள்
```bash
php artisan migrate
```

### Step 2: Sale Model Update ஆகிவிட்டது
Already done! ✅

### Step 3: Test பண்ணுங்கள்
```php
// Tinker-ல் test
php artisan tinker

// Sale Order create
$so = Sale::create([
    'transaction_type' => 'sale_order',
    'order_number' => 'SO-TEST-001',
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

// Check
Sale::saleOrders()->count(); // Should return 1
```

---

## 🎯 முக்கிய நன்மைகள்

✅ **Existing tables reuse** - புதிய tables வேண்டாம்!
✅ **Same structure** - sales_products எல்லாத்துக்கும் work ஆகும்
✅ **Easy conversion** - ஒரே method-ல் SO → Invoice
✅ **Sales rep tracking** - எந்த rep எவ்வளவு வேலை பண்ணினார்
✅ **Stock control** - Invoice ஆனதும் தான் stock குறையும்
✅ **Better reports** - எல்லா data ஒரே இடத்தில்

---

## 🤔 சாதாரண கேள்விகள்

### Q1: Sale Order-ல் payment வாங்கலாமா?
**A:** இல்லை! Sale Order just customer-ன் request மட்டும். Payment invoice ஆனதும் தான் collect பண்ணணும்.

### Q2: Stock இப்போது குறையுமா?
**A:** இல்லை! `convertToInvoice()` method call பண்ணினதும் தான் stock குறையும்.

### Q3: Sale Order cancel பண்ணலாமா?
**A:** ஆமாம்! `order_status = 'cancelled'` set பண்ணுங்கள். Stock touch ஆகாது.

### Q4: Sales Rep-கு எப்படி login தரணும்?
**A:** `sales_reps` table-ல் `user_id` இருக்கு. அந்த user-க்கு login credentials create பண்ணுங்கள்.

---

## 📞 உதவி வேண்டுமா?

**Model Methods:**
- `Sale::saleOrders()` - Sale orders மட்டும்
- `Sale::invoices()` - Invoices மட்டும்
- `Sale::pending()` - Pending orders
- `Sale::bySalesRep($id)` - Rep wise orders

**Relationships:**
- `$sale->salesRep` - Sales representative
- `$sale->convertedSale` - Converted invoice
- `$sale->originalSaleOrder` - Original SO

---

**உருவாக்கப்பட்ட தேதி:** அக்டோபர் 22, 2025  
**System:** Marazin Ultimate POS  
**மொழி:** தமிழ் 🇮🇳
