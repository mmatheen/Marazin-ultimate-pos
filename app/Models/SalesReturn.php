<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',          // If returning with an invoice
        'customer_id',      // Nullable for walk-in returns
        'location_id',      // Location of the return
        'return_date',      // Date of the return
        'return_total',     // Total value of the return
        'notes',            // Additional notes or reason for return
        'is_defective',
        'invoice_number',     // Flag for defective items
        'stock_type'
    ];

    /**
     * Relationship with Sale.
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Relationship with Customer.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relationship with Location.
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Relationship with SalesReturnProduct.
     */
    public function returnProducts()
    {
        return $this->hasMany(SalesReturnProduct::class, 'sales_return_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->invoice_number = self::generateInvoiceNumber();
        });
    }

    public static function generateInvoiceNumber()
    {
        $latest = self::latest()->first();
        $number = $latest ? intval(substr($latest->invoice_number, -4)) + 1 : 1;
        return 'SR-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
}
