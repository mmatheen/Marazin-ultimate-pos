<?php

namespace App\Models;

use App\Services\Shared\ReturnPaymentStatusService;
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
        'return_total',
        'total_paid',
        'payment_status'
    ];

    // Explicitly guard the total_due column to prevent mass assignment
    protected $guarded = ['total_due'];

    /**
     * Boot method to automatically calculate total_due
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // total_due is a generated column in the database
        });

        static::updating(function ($model) {
            // total_due is a generated column in the database
        });

        static::saving(function ($model) {
            if (!$model->isDirty('total_paid')) {
                $model->total_paid = $model->payments()->sum('amount');
            }

            $model->payment_status = app(ReturnPaymentStatusService::class)
                ->derive((float) $model->return_total, (float) $model->total_paid);
        });
    }

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

    public function payments()
    {
        return $this->hasMany(Payment::class, 'reference_id')->where('payment_type', 'purchase_return');
    }

    public function updateTotalDue()
    {
        $this->total_paid = $this->payments()->sum('amount');
        $this->save();
        $this->refresh();

        return $this;
    }

}
