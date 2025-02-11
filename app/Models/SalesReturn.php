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
        'notes',
        'is_defective',
        'invoice_number',
        'stock_type',
        'total_paid',
        'total_due',
        'payment_status'
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

    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function updatePaymentStatus()
    {
        // Calculate total paid amount
        $this->total_paid = $this->payments()->sum('amount');

        // Calculate total due amount
        $this->total_due = $this->return_total - $this->total_paid;

        // Update payment status based on total due amount
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
