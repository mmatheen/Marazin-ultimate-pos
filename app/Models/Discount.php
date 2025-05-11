<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Discount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type', // 'fixed' or 'percentage'
        'amount',
        'start_date',
        'end_date',
        'is_active',
        'apply_to_all'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
        'apply_to_all' => 'boolean'
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withTimestamps()
            ->withPivot(['created_at', 'updated_at']);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where('start_date', '<=', now())
                    ->where(function($q) {
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>=', now());
                    });
    }

    public function isCurrentlyActive(): bool
    {
        return $this->is_active &&
               $this->start_date <= now() &&
               ($this->end_date === null || $this->end_date >= now());
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($discount) {
            if ($discount->end_date !== null && $discount->end_date < now()) {
                $discount->is_active = false;
            }
        });
    }
}