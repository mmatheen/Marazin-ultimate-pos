<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;
    protected $table = 'locations';
    protected $fillable = [

        'name',
        'location_id',
        'address',
        'province',
        'district',
        'city',
        'email',
        'mobile',
        'telephone_no',
         'invoice_prefix',
    ];

    public function purchaseProducts()
    {
        return $this->hasMany(PurchaseProduct::class);
    }

    public function openingStocks()
    {
        return $this->hasMany(OpeningStock::class);
    }

    public function locationBatches()
    {
        return $this->hasMany(LocationBatch::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'location_user', 'location_id', 'user_id');
    }

    public function getInvoicePrefixAttribute()
{
    // Split the location name into words
    $words = explode(' ', $this->name);

    // Initialize the prefix
    $prefix = '';

    if (count($words) === 1) {
        $prefix = strtoupper(substr($words[0], 0, 3)); // Take first 3 letters
    } else {
        // Handle multi-word names (e.g., "ARB FASHION")
        foreach ($words as $word) {
            if (strlen($prefix) < 3 && !empty($word)) {
                $prefix .= strtoupper(substr($word, 0, 1)); // Take first letter of each word
            }
        }
    }

    return $prefix ?: 'LOC'; // Fallback to 'LOC' if no valid prefix is generated
}
}
