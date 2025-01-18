<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id', 'reference_no', 'purchase_date', 'purchasing_status',
        'location_id', 'pay_term', 'pay_term_type', 'attached_document',
        'total', 'discount_type', 'discount_amount', 'final_total', 'payment_status', 'total_paid',
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

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    // Relationship with PurchaseProduct
    public function purchaseProducts()
    {
        return $this->hasMany(PurchaseProduct::class);
    }

    // Relationship with PaymentInfo
    public function paymentInfo()
    {
        return $this->hasOne(PaymentInfo::class); // Assuming one-to-one relationship
    }
    // Define the relationship with the PurchasePayment model
    public function payments()
    {
        return $this->hasMany(PurchasePayment::class);
    }

    public function updatePaymentStatus()
    {
        $this->total_paid = $this->payments()->sum('amount');
        $this->total_due = $this->final_total - $this->total_paid;

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
