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
        'total', 'discount_type', 'discount_amount', 'final_total', 'payment_status',
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
        return $this->hasMany(PurchaseProduct::class);
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
}
