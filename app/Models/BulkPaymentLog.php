<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulkPaymentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'action',
        'entity_type',
        'payment_id',
        'entity_id',
        'customer_id',
        'supplier_id',
        'old_data',
        'new_data',
        'old_amount',
        'new_amount',
        'reference_no',
        'reason',
        'performed_by',
        'performed_at',
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
        'performed_at' => 'datetime',
    ];

    /**
     * Relationship with the user who performed the action
     */
    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Relationship with customer
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relationship with supplier
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the related entity (sale or purchase)
     */
    public function entity()
    {
        if ($this->entity_type === 'sale') {
            return $this->belongsTo(Sale::class, 'entity_id');
        } elseif ($this->entity_type === 'purchase') {
            return $this->belongsTo(Purchase::class, 'entity_id');
        }
        
        return null;
    }
}
