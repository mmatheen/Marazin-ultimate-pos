<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellDetail extends Model
{
    use HasFactory;

    protected $fillable = ['invoice_no', 'cust_id', 'added_date', 'added_by'];

    public function productOrders()
    {
        return $this->hasMany(ProductOrder::class);
    }

    public function paymentInfo()
    {
        return $this->hasOne(PaymentInfo::class);
    }

    // SellDetail model
        public function customer()
        {
            return $this->belongsTo(Customer::class, 'cust_id');
        }



}
