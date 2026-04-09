<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShortUrl extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'original_url',
        'clicks',
        'last_accessed_at',
        'expires_at',
    ];

    protected $casts = [
        'clicks' => 'integer',
        'last_accessed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
}
