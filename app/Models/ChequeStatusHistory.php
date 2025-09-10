<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChequeStatusHistory extends Model
{
    use HasFactory;

    protected $table = 'cheque_status_history'; // Fix table name to match migration

    protected $fillable = [
        'payment_id',
        'old_status',
        'new_status',
        'status_date',
        'remarks',
        'bank_charges',
        'changed_by',
    ];

    protected $dates = [
        'status_date',
    ];

    protected $casts = [
        'bank_charges' => 'decimal:2',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
