<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'reference_no',
        'location_id',
        'return_date',
        'attach_document',
        'return_total',
        'total_paid',
        'total_due',
        'payment_status'
    ];

    public function products()
    {
        return $this->hasMany(PurchaseReturnProduct::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function purchaseReturnProducts()
    {
        return $this->hasMany(PurchaseReturnProduct::class, 'purchase_return_id', 'id');
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function updatePaymentStatus()
    {
        // Calculate total paid amount
        $this->total_paid = $this->payments()->sum('amount');

        // Calculate total due amount
        $this->total_due = $this->return_total - $this->total_paid;

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
