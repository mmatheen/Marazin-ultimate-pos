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
        $name = trim($this->name); // Trim extra whitespace

        if (empty($name)) {
            return 'LOC'; // Default if name is empty
        }

        $words = preg_split('/\s+/', $name); // Split on any whitespace
        $prefix = '';

        if (count($words) === 1) {
            // Single word: take first 3 letters, pad with "0" and "C" if needed
            $word = strtoupper($words[0]);
            $prefix = substr($word, 0, 3);
            while (strlen($prefix) < 3) {
                $prefix .= '0'; // Pad with 0 if needed
            }
        } else {
            // Multiple words: take first letter of each word until we reach 3 chars
            foreach ($words as $word) {
                if (strlen($prefix) >= 3) break;
                if (!empty($word)) {
                    $prefix .= strtoupper(substr($word, 0, 1));
                }
            }

            // If still not enough letters, pad with "X"
            while (strlen($prefix) < 3) {
                $prefix .= 'X';
            }
        }

        return $prefix;
    }
}
