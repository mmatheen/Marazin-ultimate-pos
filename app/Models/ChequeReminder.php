<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChequeReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'reminder_type',
        'reminder_date',
        'is_sent',
        'sent_at',
        'sent_to',
        'notes',
    ];

    protected $dates = [
        'reminder_date',
        'sent_at',
    ];

    protected $casts = [
        'is_sent' => 'boolean',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function markAsSent($recipient = null)
    {
        $this->update([
            'is_sent' => true,
            'sent_at' => now(),
            'sent_to' => $recipient,
        ]);
    }

    public function scopePending($query)
    {
        return $query->where('is_sent', false)
                    ->where('reminder_date', '<=', now());
    }
}
