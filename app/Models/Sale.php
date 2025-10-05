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
            // Fetch or create the counter for this location
            $counter = \App\Models\InvoiceCounter::firstOrCreate(
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

            // Use current value first
            $invoiceNo = "{$prefix}-" . str_pad($counter->next_invoice_number, 3, '0', STR_PAD_LEFT);

            // Now increment it only after generating the number
            DB::table('invoice_counters')
                ->where('location_id', $locationId)
                ->increment('next_invoice_number');

            $counter->refresh(); // Optional: refresh if needed later

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
