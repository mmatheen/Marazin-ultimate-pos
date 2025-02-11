<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Supplier extends Model
{
    use HasFactory;

    protected $table = 'suppliers';
    protected $fillable = [
        'prefix',
        'first_name',
        'last_name',
        'mobile_no',
        'email',
        'address',
        'opening_balance',
        'current_balance',
        'location_id',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($supplier) {
            // Initialize current_balance with opening_balance
            $supplier->current_balance = $supplier->opening_balance;
        });
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    // Relationship with payments
    public function payments()
    {
        return $this->morphMany(Payment::class, 'entity');
    }

    // Method to update current balance
    public function updateBalance($amount)
    {
        $this->current_balance += $amount;
        $this->save();
    }
}
