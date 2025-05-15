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

    // Handle single-word names (e.g., "Sammanthurai")
    if (count($words) === 1) {
        $prefix = strtoupper(substr($words[0], 0, 3)); // Take first 3 letters
    } else {
        // Handle multi-word names (e.g., "ARB Fashion")
        foreach ($words as $word) {
            if (strlen($prefix) < 3 && !empty($word)) {
                // Take up to 2 letters from the first word and 1 letter from the second word
                $lettersToTake = (strlen($prefix) === 0) ? 2 : 1; // First word: 2 letters, others: 1 letter
                $prefix .= strtoupper(substr($word, 0, $lettersToTake));
            }
        }
    }

    return $prefix ?: 'LOC'; // Fallback to 'LOC' if no valid prefix is generated
}
}
