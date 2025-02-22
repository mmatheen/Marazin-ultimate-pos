<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'customer_id',
        'location_id',
        'return_date',
        'return_total',
        'total_paid',
        'total_due',
        'payment_status',
        'notes',
        'is_defective',
        'invoice_number',
        'stock_type',
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

    /**
     * Relationship with Payment.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class, 'reference_id', 'id')
                    ->where(function($query) {
                        $query->where('payment_type', 'sale_return_with_bill')
                              ->orWhere('payment_type', 'sale_return_without_bill');
                    });
    }

    /**
     * Boot method to generate invoice number on creating.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->invoice_number = self::generateInvoiceNumber();
        });

        static::saving(function ($model) {
            $model->total_paid = $model->payments()->sum('amount');
            $model->total_due = $model->return_total - $model->total_paid;
        });
    }

    /**
     * Generate invoice number.
     */
    public static function generateInvoiceNumber()
    {
        $latest = self::latest()->first();
        $number = $latest ? intval(substr($latest->invoice_number, -4)) + 1 : 1;
        return 'SR-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Update the total due amount.
     */
    public function updateTotalDue()
    {
        $this->total_paid = $this->payments()->sum('amount');
        $this->total_due = $this->return_total - $this->total_paid;
        $this->save();
    }
}
