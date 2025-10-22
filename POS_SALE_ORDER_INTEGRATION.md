# 🛒 POS-ல் SALE ORDER செயல்படுத்துதல் (TAMIL GUIDE)

## 🎯 கண்ணோட்டம்

உங்கள் POS page-ல் Sale Order create பண்ண இரண்டு எளிய வழிகள் உள்ளன:

---

## ✅ **வழி 1: SaleController-ஐ Update பண்ணுதல்** (பரிந்துரைக்கப்படுகிறது)

### Step 1: `storeOrUpdate` Method-ஐ Update பண்ணுங்கள்

உங்கள் existing `SaleController.php`-ல் `storeOrUpdate` method-ல் சிறிய மாற்றங்கள் செய்யுங்கள்:

#### A. Validation-ல் புதிய fields சேர்க்கவும்:

```php
// File: app/Http/Controllers/SaleController.php
// Line: ~609 (storeOrUpdate method)

$validator = Validator::make($request->all(), [
    'customer_id' => 'required|integer|exists:customers,id',
    'location_id' => 'required|integer|exists:locations,id',
    'sales_date' => 'required|date',
    'status' => 'required|string',
    'invoice_no' => 'nullable|string|unique:sales,invoice_no',
    
    // ✨ NEW: Sale Order fields
    'transaction_type' => 'nullable|string|in:invoice,sale_order', // புதிய field
    'sales_rep_id' => 'nullable|integer|exists:sales_reps,id',     // Sales rep
    'expected_delivery_date' => 'nullable|date|after_or_equal:today', // Delivery date
    'order_notes' => 'nullable|string|max:1000',                    // Customer notes
    
    'products' => 'required|array',
    // ... rest of validations remain same
]);
```

#### B. Sale Save பண்ணும்போது புதிய fields add பண்ணுங்கள்:

```php
// Around line ~817 (where $sale->fill() happens)

// Determine transaction type
$transactionType = $request->transaction_type ?? 'invoice'; // Default to invoice

// Generate appropriate number
if ($transactionType === 'sale_order') {
    // Sale Order number generation
    $orderNumber = Sale::generateOrderNumber($request->location_id);
    $orderStatus = 'pending'; // Initial status
    $invoiceNo = null; // No invoice yet
} else {
    // Normal invoice flow (existing code)
    $orderNumber = null;
    $orderStatus = null;
    // ... existing invoice_no generation code ...
}

// Save Sale with new fields
$sale->fill([
    'customer_id' => $request->customer_id,
    'location_id' => $request->location_id,
    'sales_date' => Carbon::parse($sale->created_at)->setTimezone('Asia/Colombo')->format('Y-m-d H:i:s'),
    'status' => $newStatus,
    'invoice_no' => $invoiceNo,
    'reference_no' => $referenceNo,
    'subtotal' => $subtotal,
    'final_total' => $finalTotal,
    'discount_type' => $request->discount_type,
    'discount_amount' => $discount,
    'user_id' => auth()->id(),
    'total_paid' => $totalPaid,
    'total_due' => $totalDue,
    'amount_given' => $amountGiven,
    'balance_amount' => $balanceAmount,
    
    // ✨ NEW: Sale Order specific fields
    'transaction_type' => $transactionType,
    'order_number' => $orderNumber,
    'sales_rep_id' => $request->sales_rep_id,
    'order_date' => $transactionType === 'sale_order' ? now() : null,
    'expected_delivery_date' => $request->expected_delivery_date,
    'order_status' => $orderStatus,
    'order_notes' => $request->order_notes,
])->save();
```

#### C. Sale Order-க்கு Payment Skip பண்ணுங்கள்:

```php
// Around line ~1025 (Payment handling section)

// Skip payment handling for sale orders
if ($sale->transaction_type !== 'sale_order' && $sale->status !== 'jobticket') {
    $totalPaid = 0;
    if (!empty($request->payments)) {
        // ... existing payment code ...
    }
}

// Update payment status only for invoices
if ($sale->transaction_type === 'invoice') {
    $sale->payment_status = $totalPaid >= $finalTotal ? 'Paid' : ($totalPaid > 0 ? 'Partial' : 'Due');
    $sale->save();
}
```

---

## 📱 **Step 2: POS Page-ல் UI Changes**

### A. HTML Button சேர்க்கவும்:

உங்கள் POS blade file-ல் (likely `resources/views/sell/pos.blade.php`):

```html
<!-- Existing buttons area -->
<div class="payment-buttons">
    <!-- Existing Draft/Quotation buttons -->
    
    <!-- ✨ NEW: Sale Order Button -->
    <button type="button" 
            class="btn btn-warning btn-lg" 
            id="save-sale-order"
            data-toggle="modal" 
            data-target="#saleOrderModal">
        <i class="fas fa-clipboard-list"></i>
        Sale Order
    </button>
    
    <!-- Existing Finalize Sale button -->
    <button type="button" class="btn btn-success btn-lg" id="finalize-sale">
        <i class="fas fa-check"></i>
        Finalize Sale
    </button>
</div>
```

### B. Sale Order Modal Create பண்ணுங்கள்:

```html
<!-- Sale Order Modal -->
<div class="modal fade" id="saleOrderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-clipboard-list"></i> Create Sale Order
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Sales Rep Selection -->
                <div class="form-group">
                    <label>Sales Representative <span class="text-danger">*</span></label>
                    <select class="form-control" id="sales_rep_id" required>
                        <option value="">-- Select Sales Rep --</option>
                        @foreach($salesReps as $rep)
                            <option value="{{ $rep->id }}">
                                {{ $rep->user->name }} 
                                ({{ $rep->route->name ?? 'N/A' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Expected Delivery Date -->
                <div class="form-group">
                    <label>Expected Delivery Date</label>
                    <input type="date" 
                           class="form-control" 
                           id="expected_delivery_date"
                           min="{{ date('Y-m-d') }}"
                           value="{{ date('Y-m-d', strtotime('+7 days')) }}">
                </div>

                <!-- Order Notes -->
                <div class="form-group">
                    <label>Order Notes / Instructions</label>
                    <textarea class="form-control" 
                              id="order_notes" 
                              rows="3" 
                              placeholder="Delivery instructions, special requests, etc."></textarea>
                </div>

                <!-- Order Summary -->
                <div class="alert alert-info">
                    <strong>Order Summary:</strong><br>
                    Customer: <span id="so-customer-name"></span><br>
                    Items: <span id="so-item-count"></span><br>
                    Total: ₹<span id="so-total-amount"></span>
                </div>

                <div class="alert alert-warning">
                    <i class="fas fa-info-circle"></i>
                    <strong>Note:</strong> Sale Order will be saved without payment. 
                    Stock will be reduced only when converted to invoice.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    Cancel
                </button>
                <button type="button" class="btn btn-primary" id="confirm-sale-order">
                    <i class="fas fa-save"></i> Create Sale Order
                </button>
            </div>
        </div>
    </div>
</div>
```

### C. JavaScript Code:

```javascript
// Add this to your POS JavaScript file

// When Sale Order button is clicked
$('#save-sale-order').on('click', function() {
    // Validation
    if (!validateCart()) {
        return false;
    }
    
    // Populate modal with current cart data
    const customerName = $('#customer_id option:selected').text();
    const itemCount = cartItems.length;
    const totalAmount = calculateTotal();
    
    $('#so-customer-name').text(customerName);
    $('#so-item-count').text(itemCount);
    $('#so-total-amount').text(totalAmount.toFixed(2));
    
    // Show modal
    $('#saleOrderModal').modal('show');
});

// Confirm Sale Order creation
$('#confirm-sale-order').on('click', function() {
    const salesRepId = $('#sales_rep_id').val();
    
    if (!salesRepId) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Please select a Sales Representative'
        });
        return;
    }
    
    // Prepare sale order data
    const saleOrderData = prepareSaleData();
    
    // Add Sale Order specific fields
    saleOrderData.transaction_type = 'sale_order';
    saleOrderData.sales_rep_id = salesRepId;
    saleOrderData.expected_delivery_date = $('#expected_delivery_date').val();
    saleOrderData.order_notes = $('#order_notes').val();
    saleOrderData.status = 'draft'; // or 'pending'
    saleOrderData.payments = []; // No payments for sale order
    
    // Submit via AJAX
    $.ajax({
        url: '/sales/store',
        method: 'POST',
        data: saleOrderData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.status === 200 || response.status === 201) {
                $('#saleOrderModal').modal('hide');
                
                Swal.fire({
                    icon: 'success',
                    title: 'Sale Order Created!',
                    html: `<strong>Order Number:</strong> ${response.sale.order_number}<br>
                           <strong>Customer:</strong> ${response.sale.customer.first_name}<br>
                           <strong>Total:</strong> ₹${response.sale.final_total}`,
                    showConfirmButton: true,
                    confirmButtonText: 'View Orders'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '/sale-orders';
                    } else {
                        // Clear cart and reset
                        clearCart();
                    }
                });
            }
        },
        error: function(xhr) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: xhr.responseJSON?.message || 'Failed to create sale order'
            });
        }
    });
});

// Helper function to validate cart
function validateCart() {
    if (cartItems.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Empty Cart',
            text: 'Please add items to cart first'
        });
        return false;
    }
    
    const customerId = $('#customer_id').val();
    if (!customerId || customerId == 1) {
        Swal.fire({
            icon: 'warning',
            title: 'Invalid Customer',
            text: 'Please select a valid customer (not Walk-in)'
        });
        return false;
    }
    
    return true;
}
```

---

## 🎯 **Step 3: Controller-ல் Sales Reps Pass பண்ணுங்கள்**

```php
// File: app/Http/Controllers/SaleController.php

public function pos()
{
    // Get active sales reps for dropdown
    $salesReps = \App\Models\SalesRep::active()
        ->with(['user', 'route'])
        ->get();
    
    return view('sell.pos', compact('salesReps'));
}
```

---

## 🔄 **Step 4: Response-ல் Order Number Return பண்ணுங்கள்**

```php
// At the end of storeOrUpdate method (around line ~1200)

// Success response
return response()->json([
    'status' => 200,
    'message' => $sale->transaction_type === 'sale_order' 
        ? 'Sale Order created successfully!' 
        : 'Sale created successfully!',
    'sale' => $sale->load(['customer', 'products.product', 'salesRep.user']),
    'invoice_no' => $sale->invoice_no,
    'order_number' => $sale->order_number, // ✨ NEW
    'reference_no' => $sale->reference_no,
]);
```

---

## 📋 **எளிய Summary**

### POS-ல் Sale Order Create பண்ண:

1. ✅ **Items add** பண்ணுங்கள் cart-ல்
2. ✅ **Customer select** பண்ணுங்கள் (Walk-in இல்லாமல்)
3. ✅ **"Sale Order" button** click பண்ணுங்கள்
4. ✅ **Sales Rep select** பண்ணுங்கள்
5. ✅ **Delivery date** மற்றும் **notes** enter பண்ணுங்கள்
6. ✅ **"Create Sale Order"** click பண்ணுங்கள்

### பின்னர்:
- Order number create ஆகும் (SO-2025-0001)
- Status: `pending` or `draft`
- **Payment இல்லை** (Later convert பண்ணும்போது)
- **Stock குறையாது** (Invoice ஆனதும் தான் குறையும்)

---

## 🎨 **Optional: Sale Order List Page**

Create a simple list to view all sale orders:

```php
// Route
Route::get('/sale-orders', [SaleOrderController::class, 'index'])
    ->name('sale-orders.index');

// Controller method
public function index()
{
    $saleOrders = Sale::saleOrders()
        ->with(['customer', 'salesRep.user', 'products'])
        ->latest('order_date')
        ->paginate(20);
    
    return view('sale-orders.index', compact('saleOrders'));
}
```

---

## ✅ **Testing Checklist**

- [ ] POS-ல் Sale Order button தெரியுதா?
- [ ] Sales Reps dropdown-ல் வருதா?
- [ ] Sale Order create ஆகுதா?
- [ ] Order number generate ஆகுதா?
- [ ] Database-ல் transaction_type = 'sale_order' save ஆகுதா?
- [ ] Payment fields empty-யா இருக்கு?
- [ ] Success message காட்டுதா?

---

## 🆘 **Common Issues**

### Issue 1: Sales Reps dropdown empty
**Solution:** Database-ல் sales_reps table-ல் data இருக்கானு check பண்ணுங்கள்:
```sql
SELECT * FROM sales_reps WHERE status = 'active';
```

### Issue 2: Order number null
**Solution:** Migration run பண்ணீங்களான check பண்ணுங்கள்:
```bash
php artisan migrate:status
```

### Issue 3: Validation error
**Solution:** `transaction_type` field validation-ல் சேர்த்துருக்கீங்களான check பண்ணுங்கள்.

---

**Created:** October 22, 2025  
**System:** Marazin Ultimate POS  
**Language:** Tamil + English
