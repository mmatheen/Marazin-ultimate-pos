<?php

namespace App\Models;

use App\Traits\LocationTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Sale extends Model
{
    use HasFactory, LocationTrait;

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

    ];

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
    public static function generateInvoiceNo($locationId)
    {
        return DB::transaction(function () use ($locationId) {
            $location = Location::findOrFail($locationId);
    
            $prefix = $location->invoice_prefix;
    
            // Lock latest sale for this location
            $lastSale = self::where('location_id', $locationId)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();
    
            if ($lastSale && preg_match("/{$prefix}-(\d+)/", $lastSale->invoice_no, $matches)) {
                $nextNumber = (int)$matches[1] + 1;
            } else {
                $nextNumber = 1;
            }
    
            $invoiceNo = "{$prefix}-" . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    
            // Double-check uniqueness just in case
            while (self::where('location_id', $locationId)->where('invoice_no', $invoiceNo)->exists()) {
                $nextNumber++;
                $invoiceNo = "{$prefix}-" . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }
    
            return $invoiceNo;
        });
    }
    public function payments()
    {
        return $this->hasMany(Payment::class, 'reference_id', 'id')->where('payment_type', 'sale');
    }

    public function getTotalPaidAttribute()
    {
        return $this->payments()->sum('amount');
    }

    public function getTotalDueAttribute()
    {
        return $this->final_total - $this->total_paid;
    }

    public function updateTotalDue()
    {
        $this->total_paid = $this->payments()->sum('amount');
        $this->total_due = $this->final_total - $this->total_paid;
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
}
