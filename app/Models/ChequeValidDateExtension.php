<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChequeValidDateExtension extends Model
{
    protected $fillable = [
        'payment_id',
        'previous_valid_date',
        'new_valid_date',
        'reason',
        'extended_by',
    ];

    protected $casts = [
        'previous_valid_date' => 'date',
        'new_valid_date' => 'date',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'extended_by');
    }
}
