<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashRegister extends Model
{
    use HasFactory;

    protected $table = 'cash_registers';

    protected $fillable = [
        'location_id',
        'user_id',
        'opening_amount',
        'opening_at',
        'closing_at',
        'closing_amount',
        'expected_balance',
        'difference',
        'status',
        'notes',
        'closed_by',
    ];

    protected $casts = [
        'opening_amount'   => 'decimal:2',
        'closing_amount'   => 'decimal:2',
        'expected_balance' => 'decimal:2',
        'difference'        => 'decimal:2',
        'opening_at'        => 'datetime',
        'closing_at'        => 'datetime',
    ];

    // Relationships
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function transactions()
    {
        return $this->hasMany(CashRegisterTransaction::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'cash_register_id');
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeForLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Helpers
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}
