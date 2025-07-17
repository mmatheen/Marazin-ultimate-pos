<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Waiter extends Model
{
    use HasFactory;

    // Optional: only needed if your table name is different
    protected $table = 'waiters';

    // Fillable attributes for mass assignment
    protected $fillable = [
        'name',
        'phone',
    ];

    /**
     * Many-to-many relationship with Table
     * A waiter can be assigned to many tables
     */
    public function tables()
    {
        return $this->belongsToMany(Table::class, 'table_waiter', 'waiter_id', 'table_id')->withTimestamps();
    }
}
