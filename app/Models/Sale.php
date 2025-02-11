<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

        protected $fillable = [
            'customer_id',
            'sales_date',
            'location_id',
            'status',
            'invoice_no',
            'final_total',
            'total_paid',
            'total_due',
            'payment_status'
        ];

    public function products()
    {
        return $this->hasMany(SalesProduct::class);
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
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

    public static function getAvailableStock($batchId, $locationId)
    {
        $locationBatch = LocationBatch::where('batch_id', $batchId)
            ->where('location_id', $locationId)
            ->first();

        return $locationBatch ? $locationBatch->qty : 0;
    }

    public function getBatchQuantityPlusSold($batchId, $locationId, $productId)
    {
        // Get available stock from the batch in the location
        $availableStock = self::getAvailableStock($batchId, $locationId);

        // Get sold quantity for this product and batch from the sale
        $soldQuantity = $this->products()
            ->where('product_id', $productId)
            ->where('batch_id', $batchId)
            ->sum('quantity');

        return $availableStock + $soldQuantity;
    }

    public function updatePaymentStatus()
    {
        // Calculate total paid amount
        $this->total_paid = $this->payments()->sum('amount');

        // Calculate total due amount
        $this->total_due = $this->final_total - $this->total_paid;

        // Update payment status based on total due amount
        if ($this->total_due <= 0) {
            $this->payment_status = 'Paid';
        } elseif ($this->total_paid > 0) {
            $this->payment_status = 'Partial';
        } else {
            $this->payment_status = 'Due';
        }

        $this->save();
    }
}
