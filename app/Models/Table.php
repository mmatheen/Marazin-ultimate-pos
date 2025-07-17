<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    use HasFactory;

    // Optional: If your table name is exactly 'tables', you can remove this line
    protected $table = 'tables';

    // Columns that can be mass assigned
    protected $fillable = [
        'name',
        'capacity',
        'is_available',
    ];

    // Cast is_available to boolean automatically
    protected $casts = [
        'is_available' => 'boolean',
    ];

    /**
     * Many-to-many relationship with Waiter
     * A table can have many waiters assigned
     */
    public function waiters()
    {
        return $this->belongsToMany(Waiter::class, 'table_waiter', 'table_id', 'waiter_id')->withTimestamps();
    }
}
