<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 'sales_date', 'location_id','status', 'invoice_no', 'additional_notes',
        'shipping_details', 'shipping_address', 'shipping_charges', 'shipping_status',
        'delivered_to', 'delivery_person',
    ];

    public function products()
    {
        return $this->hasMany(SalesProduct::class);
    }

    public function payments()
    {
        return $this->hasMany(SalesPayment::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
