<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'sell_detail_id',
        'purchase_id',
        'payment_date',
        'reference_num',
        'amount',
        'payment_mode',
        'payment_status'
    ];
}
