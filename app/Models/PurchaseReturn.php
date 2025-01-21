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


}
