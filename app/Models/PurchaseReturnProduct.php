<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturnProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_return_id',
        'product_id',
        'batch_no',
        'quantity',
        'free_quantity',     // Free items returned to supplier
        'unit_price',
        'subtotal',
    ];

    public function purchaseReturn()
    {
        return $this->belongsTo(PurchaseReturn::class, 'purchase_return_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batch_no', 'id'); // Ensure that 'id' is the correct column in the Batch model
    }
}
