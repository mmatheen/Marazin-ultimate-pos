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
        'payment_status'
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
        return $this->hasMany(Payment::class, 'reference_id', 'id')->where('payment_type', 'purchase');
    }

    public function getTotalPaidAttribute()
    {
        return $this->payments()->sum('amount');
    }

    public function getTotalDueAttribute()
    {
        return $this->final_total - $this->total_paid;
    }
    // Update the total due amount
    public function updateTotalDue()
    {
        $this->total_due = $this->final_total - $this->total_paid;
        $this->save();
    }
}
