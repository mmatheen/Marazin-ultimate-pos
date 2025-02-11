<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'reference_no',
        'purchase_date',
        'purchasing_status',
        'location_id',
        'pay_term',
        'pay_term_type',
        'attached_document',
        'total',
        'discount_type',
        'discount_amount',
        'final_total',
        'payment_status',
        'total_paid',
        'total_due'
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function purchaseProducts()
    {
        return $this->hasMany(PurchaseProduct::class);
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
