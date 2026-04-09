<?php

namespace App\Models;

use App\Traits\LocationTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\CustomLogsActivity;
use App\Services\Sale\SaleCalculationService;
use App\Services\Sale\SaleNumberingService;
use App\Services\Sale\SalePaymentStatusService;

class Sale extends Model
{
    use HasFactory, LocationTrait, LogsActivity, CustomLogsActivity;

    protected string $customLogName = 'sale';

    protected $fillable = [
        'customer_id',
        'sales_date',
        'location_id',
        'user_id',
        'updated_by',
        'status',
        'sale_type',
        'invoice_no',
        'subtotal',
        'total_paid',
        'total_due',
        'payment_status',
        'sale_notes',
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
        // Shipping fields
        'shipping_details',
        'shipping_address',
        'shipping_charges',
        'shipping_status',
        'delivered_to',
        'delivery_person',
        'invoice_token',
    ];


    protected static function booted()
    {
        static::addGlobalScope(new \App\Scopes\LocationScope);
    }

    /**
     * Compatibility wrapper for the extracted payment-status service.
     *
     * @deprecated Use App\Services\Sale\SalePaymentStatusService directly.
     */
    public static function derivePaymentStatusForInvoice(float $finalTotal, ?float $totalPaid): string
    {
        return app(SalePaymentStatusService::class)->deriveForInvoice($finalTotal, $totalPaid);
    }

    // Add this method to your Sale model
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship: User who last updated this sale
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function products()
    {
        return $this->hasMany(SalesProduct::class)->orderBy('id');
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
     * Compatibility wrapper for the extracted numbering service.
     *
     * @deprecated Use App\Services\Sale\SaleNumberingService directly.
     */
    public static function generateOrderNumber($locationId)
    {
        return app(SaleNumberingService::class)->generateOrderNumber((int) $locationId);
    }

    // convertToInvoice(), revertToSaleOrder(), validateStockAvailability(), and
    // updateStockOnConversion() have been extracted to SaleOrderConversionService.

    // getAvailableStock() moved to SaleValidationService::getAvailableStock().
    // getBatchQuantityPlusSold() removed — no callers.

    /**
     * Compatibility wrapper for the extracted numbering service.
     *
     * @deprecated Use App\Services\Sale\SaleNumberingService directly.
     */
    public static function generateInvoiceNo($locationId)
    {
        return app(SaleNumberingService::class)->generateInvoiceNo((int) $locationId);
    }
    public function payments()
    {
        return $this->hasMany(Payment::class, 'reference_id', 'id')->where('payment_type', 'sale');
    }

    // Removed getTotalPaidAttribute() since total_paid is now a database column
    // Removed getTotalDueAttribute() since total_due is auto-generated by database

    // updateTotalDue() removed — dead code (no callers).
    // boot() merged into booted() above.

    /**
     * Compatibility wrapper for the extracted calculation service.
     *
     * @deprecated Use App\Services\Sale\SaleCalculationService directly.
     */
    public function calculateFinalTotal()
    {
        return app(SaleCalculationService::class)->calculateFinalTotal($this);
    }

    public function imeis()
    {
        return $this->hasMany(SaleImei::class);
    }

    // recalculatePaymentTotals() extracted to SalePaymentProcessor::recalculatePaymentTotals(Sale $sale).

    // ==================== SHIPPING METHODS ====================

    /**
     * Check if sale has shipping information
     */
    public function hasShipping()
    {
        return !empty($this->shipping_details) || !empty($this->shipping_address) || $this->shipping_charges > 0;
    }

    /**
     * Get formatted shipping status
     */
    public function getFormattedShippingStatusAttribute()
    {
        return ucfirst($this->shipping_status ?? 'pending');
    }

    /**
     * Check if order is shipped
     */
    public function isShipped()
    {
        return $this->shipping_status === 'shipped';
    }

    /**
     * Check if order is delivered
     */
    public function isDelivered()
    {
        return $this->shipping_status === 'delivered';
    }

    /**
     * Mark as shipped — updates only columns that exist in the DB.
     * (shipped_at / tracking_number have no DB column — not stored here)
     */
    public function markAsShipped($trackingNumber = null, $deliveryPerson = null)
    {
        $data = ['shipping_status' => 'shipped'];
        if ($deliveryPerson !== null) {
            $data['delivery_person'] = $deliveryPerson;
        }
        $this->update($data);
    }

    /**
     * Mark as delivered — updates only columns that exist in the DB.
     * (delivered_at has no DB column — not stored here)
     */
    public function markAsDelivered($deliveredTo = null)
    {
        $this->update([
            'shipping_status' => 'delivered',
            'delivered_to'    => $deliveredTo ?? $this->delivered_to,
        ]);
    }

    /**
     * Get total including shipping charges.
     * NOTE: final_total already includes shipping_charges (see calculateFinalTotal).
     * This accessor returns final_total as-is — shipping is NOT added again.
     */
    public function getTotalWithShippingAttribute()
    {
        return $this->final_total;
    }

    /**
     * Scope: Filter by shipping status
     */
    public function scopeByShippingStatus($query, $status)
    {
        return $query->where('shipping_status', $status);
    }

    /**
     * Scope: Get sales with shipping
     */
    public function scopeWithShipping($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('shipping_details')
              ->orWhereNotNull('shipping_address')
              ->orWhere('shipping_charges', '>', 0);
        });
    }
}
