<?php

namespace App\Models;

use App\Traits\LocationTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\CustomLogsActivity;

class Sale extends Model
{
    use HasFactory, LocationTrait, LogsActivity, CustomLogsActivity;

    protected string $customLogName = 'sale';

    protected $fillable = [
        'customer_id',
        'sales_date',
        'location_id',
        'user_id',
        'status',
        'sale_type',
        'invoice_no',
        'subtotal',
        'total_paid',
        'total_due',
        'payment_status',
        'discount_type',
        'discount_amount',
        'amount_given',
        'balance_amount',
        'final_total',
        // Sale Order fields (no sales_rep_id - we use user_id)
        'transaction_type',
        'order_number',
        'order_date',
        'expected_delivery_date',
        'order_status',
        'converted_to_sale_id',
        'order_notes',
    ];


    protected static function booted()
    {
        static::addGlobalScope(new \App\Scopes\LocationScope);
        
        // Automatically calculate total_due before saving
        static::saving(function ($sale) {
            if (isset($sale->final_total) && isset($sale->total_paid)) {
                $sale->total_due = $sale->final_total - $sale->total_paid;
            }
        });
    }


    // Add this method to your Sale model
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->hasMany(SalesProduct::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function salesReturns()
    {
        return $this->hasMany(SalesReturn::class);
    }

    /**
     * Relationship: Sales Representative who created this order
     * Uses existing user relationship - if user is a sales rep
     */
    public function salesRep()
    {
        // Get the sales rep record for this user
        return $this->hasOneThrough(
            \App\Models\SalesRep::class,
            \App\Models\User::class,
            'id', // Foreign key on users table
            'user_id', // Foreign key on sales_reps table
            'user_id', // Local key on sales table
            'id' // Local key on users table
        );
    }

    /**
     * Relationship: If this is a sale order, the converted invoice
     */
    public function convertedSale()
    {
        return $this->belongsTo(Sale::class, 'converted_to_sale_id');
    }

    /**
     * Relationship: If this is an invoice, the original sale order
     */
    public function originalSaleOrder()
    {
        return $this->hasOne(Sale::class, 'converted_to_sale_id', 'id');
    }

    // Function to get the total quantity of items sold for a specific product in this sale
    public function getTotalSoldQuantity($productId)
    {
        return $this->products()->where('product_id', $productId)->sum('quantity');
    }

    // Function to get the total quantity of items returned for a specific product in this sale
    public function getTotalReturnedQuantity($productId)
    {
        return $this->hasManyThrough(SalesReturnProduct::class, SalesReturn::class, 'sale_id', 'sales_return_id')
            ->where('product_id', $productId)
            ->sum('quantity');
    }

    // Function to get the current sale quantity for a specific product after accounting for returns
    public function getCurrentSaleQuantity($productId)
    {
        $totalSoldQuantity = $this->getTotalSoldQuantity($productId);
        $totalReturnedQuantity = $this->getTotalReturnedQuantity($productId);

        return $totalSoldQuantity - $totalReturnedQuantity;
    }

    // ============================================
    // SALE ORDER SPECIFIC METHODS
    // ============================================

    /**
     * Scope: Get only Sale Orders
     */
    public function scopeSaleOrders($query)
    {
        return $query->where('transaction_type', 'sale_order');
    }

    /**
     * Scope: Get only Invoices (completed sales)
     */
    public function scopeInvoices($query)
    {
        return $query->where('transaction_type', 'invoice');
    }

    /**
     * Scope: Get pending sale orders
     */
    public function scopePending($query)
    {
        return $query->where('transaction_type', 'sale_order')
                    ->whereIn('order_status', ['draft', 'pending', 'confirmed']);
    }

    /**
     * Scope: Get sale orders by user (sales rep)
     * Since we use user_id instead of sales_rep_id
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
    
    /**
     * Scope: Get sale orders by sales rep (using user_id from sales_reps table)
     * @deprecated Use byUser() instead
     */
    public function scopeBySalesRep($query, $salesRepId)
    {
        // Get user_id from sales_reps table
        $salesRep = \App\Models\SalesRep::find($salesRepId);
        if ($salesRep) {
            return $query->where('user_id', $salesRep->user_id);
        }
        return $query->whereRaw('1 = 0'); // Return empty if not found
    }

    /**
     * Check if this is a Sale Order
     */
    public function isSaleOrder()
    {
        return $this->transaction_type === 'sale_order';
    }

    /**
     * Check if this is an Invoice
     */
    public function isInvoice()
    {
        return $this->transaction_type === 'invoice';
    }

    /**
     * Generate unique Sale Order number
     */
    public static function generateOrderNumber($locationId)
    {
        return DB::transaction(function () use ($locationId) {
            $location = Location::findOrFail($locationId);
            $prefix = $location->invoice_prefix ?? 'SO';

            // Get last order number for this location
            $lastOrder = self::where('location_id', $locationId)
                ->where('transaction_type', 'sale_order')
                ->whereNotNull('order_number')
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            $nextNumber = 1;
            if ($lastOrder && preg_match("/{$prefix}-SO-(\d+)/", $lastOrder->order_number, $matches)) {
                $nextNumber = (int)$matches[1] + 1;
            }

            $orderNumber = "{$prefix}-SO-" . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            // Ensure uniqueness
            while (self::where('order_number', $orderNumber)->exists()) {
                $nextNumber++;
                $orderNumber = "{$prefix}-SO-" . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            }

            return $orderNumber;
        });
    }

    /**
     * Convert Sale Order to Invoice
     */
    public function convertToInvoice()
    {
        if (!$this->isSaleOrder()) {
            throw new \Exception('Only Sale Orders can be converted to invoices');
        }

        if ($this->order_status === 'completed') {
            throw new \Exception('This Sale Order has already been converted');
        }

        // ✅ BUSINESS RULE: Order must be confirmed before conversion
        if ($this->order_status === 'pending') {
            throw new \Exception('Cannot convert pending orders. Please confirm the order first.');
        }

        if ($this->order_status === 'cancelled') {
            throw new \Exception('Cannot convert cancelled orders.');
        }

        if ($this->order_status === 'on_hold') {
            throw new \Exception('Cannot convert orders on hold. Please change status first.');
        }

        // ✅ VALIDATE STOCK BEFORE CONVERSION
        $this->validateStockAvailability();

        // ✅ Load IMEI numbers for products
        $this->load('products.imeis');

        return DB::transaction(function () {
            // Create new invoice record
            $invoice = new Sale();
            $invoice->fill([
                'transaction_type' => 'invoice',
                'customer_id' => $this->customer_id,
                'location_id' => $this->location_id,
                'user_id' => $this->user_id, // Keep same user (sales rep)
                'sales_date' => now(),
                'sale_type' => $this->sale_type,
                'status' => 'final',
                'subtotal' => $this->subtotal,
                'discount_type' => $this->discount_type,
                'discount_amount' => $this->discount_amount,
                'final_total' => $this->final_total,
                'total_paid' => 0,
                'payment_status' => 'Due',
                'order_notes' => $this->order_notes,
            ]);
            
            // Generate invoice number
            $invoice->invoice_no = self::generateInvoiceNo($this->location_id);
            $invoice->save();

            // Copy sale order items to invoice
            foreach ($this->products as $item) {
                $newItem = new SalesProduct();
                $newItem->fill([
                    'sale_id' => $invoice->id,
                    'product_id' => $item->product_id,
                    'batch_id' => $item->batch_id,
                    'location_id' => $item->location_id,
                    'quantity' => $item->quantity,
                    'price_type' => $item->price_type,
                    'price' => $item->price,
                    'discount_amount' => $item->discount_amount,
                    'discount_type' => $item->discount_type,
                    'tax' => $item->tax,
                ]);
                $newItem->save();

                // ✅ Copy IMEI numbers from sale order to invoice
                // Load IMEIs if not already loaded
                if (!$item->relationLoaded('imeis')) {
                    $item->load('imeis');
                }
                
                Log::info("Converting product {$item->product_id}, IMEI count: " . $item->imeis->count());
                
                if ($item->imeis && $item->imeis->count() > 0) {
                    foreach ($item->imeis as $imei) {
                        $newImei = new SaleImei();
                        $newImei->fill([
                            'sale_id' => $invoice->id,
                            'sale_product_id' => $newItem->id,
                            'product_id' => $imei->product_id,
                            'batch_id' => $imei->batch_id,
                            'location_id' => $imei->location_id,
                            'imei_number' => $imei->imei_number,
                        ]);
                        $newImei->save();
                        Log::info("Copied IMEI: {$imei->imei_number} to invoice {$invoice->id}");
                    }
                } else {
                    Log::warning("No IMEIs found for product {$item->product_id} in sale order {$this->id}");
                }

                // Update stock (reduce inventory)
                $this->updateStockOnConversion($item);
            }

            // Mark sale order as completed
            $this->update([
                'order_status' => 'completed',
                'converted_to_sale_id' => $invoice->id,
            ]);

            Log::info("Sale Order {$this->order_number} converted to Invoice {$invoice->invoice_no}", [
                'sale_order_id' => $this->id,
                'invoice_id' => $invoice->id,
                'invoice_no' => $invoice->invoice_no,
                'stock_reduced' => true
            ]);

            return $invoice;
        });
    }

    /**
     * Revert invoice conversion (for cancelled invoices)
     * This restores stock and changes sale order back to "confirmed"
     */
    public function revertInvoiceConversion($invoiceId)
    {
        return DB::transaction(function () use ($invoiceId) {
            $invoice = Sale::findOrFail($invoiceId);
            
            // Validate this is an invoice converted from this sale order
            if ($invoice->transaction_type !== 'invoice') {
                throw new \Exception('Not an invoice');
            }
            
            if ($this->converted_to_sale_id != $invoiceId) {
                throw new \Exception('This invoice was not created from this sale order');
            }
            
            // Check if invoice has payments
            if ($invoice->total_paid > 0) {
                throw new \Exception('Cannot revert invoice with payments. Please process refund first.');
            }
            
            // Restore stock for each product
            foreach ($invoice->products as $item) {
                $locationBatch = LocationBatch::where('batch_id', $item->batch_id)
                    ->where('location_id', $item->location_id)
                    ->first();
                
                if ($locationBatch) {
                    $locationBatch->increment('qty', $item->quantity);
                }
            }
            
            // Mark invoice as cancelled
            $invoice->update([
                'status' => 'cancelled',
                'payment_status' => 'Cancelled'
            ]);
            
            // Restore sale order to confirmed status
            $this->update([
                'order_status' => 'confirmed',
                'converted_to_sale_id' => null,
            ]);
            
            Log::info("Invoice {$invoice->invoice_no} reverted, Sale Order {$this->order_number} restored to confirmed", [
                'sale_order_id' => $this->id,
                'invoice_id' => $invoice->id,
                'stock_restored' => true
            ]);
            
            return true;
        });
    }

    /**
     * Validate stock availability before conversion
     */
    protected function validateStockAvailability()
    {
        $insufficientItems = [];
        
        foreach ($this->products as $item) {
            // Get current stock for this batch/location
            $locationBatch = \App\Models\LocationBatch::where('batch_id', $item->batch_id)
                ->where('location_id', $item->location_id)
                ->first();
            
            $availableQty = $locationBatch ? $locationBatch->qty : 0;
            
            if ($availableQty < $item->quantity) {
                $product = $item->product;
                $productName = $product ? $product->product_name : 'Unknown Product';
                
                $insufficientItems[] = [
                    'product' => $productName,
                    'required' => $item->quantity,
                    'available' => $availableQty,
                    'shortage' => $item->quantity - $availableQty
                ];
            }
        }
        
        if (!empty($insufficientItems)) {
            $errorMessage = "Insufficient stock for the following items:\n\n";
            foreach ($insufficientItems as $item) {
                $errorMessage .= "• {$item['product']}: Required {$item['required']}, Available {$item['available']} (Short by {$item['shortage']})\n";
            }
            $errorMessage .= "\nPlease restock or reduce order quantities before converting to invoice.";
            
            throw new \Exception($errorMessage);
        }
    }

    /**
     * Update stock when converting SO to Invoice
     */
    protected function updateStockOnConversion($item)
    {
        // Update location_batches stock
        $locationBatch = LocationBatch::where('batch_id', $item->batch_id)
            ->where('location_id', $item->location_id)
            ->first();

        if ($locationBatch) {
            $locationBatch->decrement('qty', $item->quantity);
        }

        // Note: Stock is managed through location_batch table, not products.stock column
    }

    public static function getAvailableStock($batchId, $locationId)
    {
        $locationBatch = LocationBatch::where('batch_id', $batchId)
            ->where('location_id', $locationId)
            ->first();

        return $locationBatch ? $locationBatch->qty : 0;
    }

    public function getBatchQuantityPlusSold($batchId, $locationId, $productId)
    {
        if ($batchId === 'all') {
            // Get total available stock for all batches of the product in the location
            $availableStock = DB::table('location_batches')
                ->join('batches', 'location_batches.batch_id', '=', 'batches.id')
                ->where('batches.product_id', $productId)
                ->where('location_batches.location_id', $locationId)
                ->sum('location_batches.qty');

            // Get total sold quantity for all batches of the product in this sale
            $soldQuantity = $this->products()
                ->where('product_id', $productId)
                ->sum('quantity');
        } else {
            // Get available stock from the specific batch in the location
            $availableStock = self::getAvailableStock($batchId, $locationId);

            // Get sold quantity for this specific batch in this sale
            $soldQuantity = $this->products()
                ->where('product_id', $productId)
                ->where('batch_id', $batchId)
                ->sum('quantity');
        }

        return $availableStock + $soldQuantity;
    }
    // public static function generateInvoiceNo($locationId)
    // {
    //     return DB::transaction(function () use ($locationId) {
    //         $location = Location::findOrFail($locationId);

    //         $prefix = $location->invoice_prefix;

    //         // Lock the sales table for this location
    //         $lastSale = self::where('location_id', $locationId)
    //             ->lockForUpdate()
    //             ->orderByDesc('id')
    //             ->first();

    //         $nextNumber = 1;

    //         if ($lastSale && preg_match("/{$prefix}-(\d+)/", $lastSale->invoice_no, $matches)) {
    //             $nextNumber = (int)$matches[1] + 1;
    //         }

    //         $invoiceNo = "{$prefix}-" . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

    //         // Ensure the invoice number is unique
    //         while (self::where('location_id', $locationId)->where('invoice_no', $invoiceNo)->exists()) {
    //             $nextNumber++;
    //             $invoiceNo = "{$prefix}-" . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    //         }

    //         return $invoiceNo;
    //     });
    // }

    public static function generateInvoiceNo($locationId)
    {
        return DB::transaction(function () use ($locationId) {
            // Lock the counter row to prevent race conditions
            $counter = \App\Models\InvoiceCounter::lockForUpdate()
                ->firstOrCreate(
                    ['location_id' => $locationId],
                    ['next_invoice_number' => 1]
                );

            // Get prefix from Location and adjust for "Arf fashion" to "AFS"
            $location = \App\Models\Location::findOrFail($locationId);
            $prefix = $location->invoice_prefix;

            // If the prefix is "AFX", change it to "AFS"
            if (strtoupper($prefix) === 'AFX') {
                $prefix = 'AFS';
            }

            // Generate invoice number with current counter
            $invoiceNo = "{$prefix}-" . str_pad($counter->next_invoice_number, 3, '0', STR_PAD_LEFT);

            // Check if this invoice number already exists (safety check)
            $attempts = 0;
            $maxAttempts = 10;
            
            while (self::where('invoice_no', $invoiceNo)->exists() && $attempts < $maxAttempts) {
                $counter->next_invoice_number++;
                $invoiceNo = "{$prefix}-" . str_pad($counter->next_invoice_number, 3, '0', STR_PAD_LEFT);
                $attempts++;
            }

            // Increment for next invoice
            $counter->next_invoice_number++;
            $counter->save();

            return $invoiceNo;
        });
    }
    public function payments()
    {
        return $this->hasMany(Payment::class, 'reference_id', 'id')->where('payment_type', 'sale');
    }

    // Removed getTotalPaidAttribute() since total_paid is now a database column
    // Removed getTotalDueAttribute() since total_due is auto-generated by database

    public function updateTotalDue()
    {
        $this->total_paid = $this->payments()->sum('amount');
        // total_due is auto-calculated by the database, no need to set manually
        $this->save();
    }

    // Ensure final_total is calculated correctly before saving
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($sale) {
            $sale->final_total = $sale->calculateFinalTotal();
        });
    }

    public function calculateFinalTotal()
    {
        if ($this->discount_type === 'percentage') {
            return $this->subtotal - ($this->subtotal * $this->discount_amount / 100);
        } else {
            return $this->subtotal - $this->discount_amount;
        }
    }

    public function imeis()
    {
        return $this->hasMany(SaleImei::class);
    }

    /**
     * Recalculate payment totals considering cheque status
     */
    public function recalculatePaymentTotals()
    {
        // Get all payments regardless of cheque status (for sale completion)
        $totalReceived = $this->payments()->sum('amount');
        
        // Get actual cleared/safe payments (for risk analysis)
        $actualPaid = $this->payments()
            ->where(function($query) {
                $query->where('payment_method', '!=', 'cheque')
                      ->orWhere(function($subQuery) {
                          $subQuery->where('payment_method', 'cheque')
                                   ->where('cheque_status', 'cleared');
                      });
            })
            ->sum('amount');

        // Get pending cheque amounts (risk tracking)
        $pendingCheques = $this->payments()
            ->where('payment_method', 'cheque')
            ->whereIn('cheque_status', ['pending', 'deposited'])
            ->sum('amount');

        // Get bounced cheque amounts
        $bouncedCheques = $this->payments()
            ->where('payment_method', 'cheque')
            ->where('cheque_status', 'bounced')
            ->sum('amount');

        // For sale completion: Count all payments EXCEPT bounced cheques
        $newTotalPaid = $totalReceived - $bouncedCheques;
        
        // Debug logging
        Log::info("Sale {$this->id} payment recalculation:", [
            'total_received' => $totalReceived,
            'bounced_cheques' => $bouncedCheques,
            'old_total_paid' => $this->total_paid,
            'new_total_paid' => $newTotalPaid,
            'final_total' => $this->final_total
        ]);
        
        $this->total_paid = $newTotalPaid;
        $this->total_due = $this->final_total - $newTotalPaid; // Now we can set this directly
        
        // Update payment status based on totals
        if ($this->total_due <= 0) {
            $this->payment_status = 'Paid';
        } elseif ($this->total_paid > 0) {
            $this->payment_status = 'Partial';
        } else {
            $this->payment_status = 'Due';
        }

        $this->save();
        
        return [
            'total_received' => $totalReceived,
            'actual_paid' => $actualPaid,
            'pending_cheques' => $pendingCheques,
            'bounced_cheques' => $bouncedCheques,
            'total_due' => $this->total_due,
            'at_risk_amount' => $pendingCheques
        ];
    }
}
