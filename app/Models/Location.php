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
        'parent_id',
    ];

    public function parent()
    {
        return $this->belongsTo(Location::class, 'parent_id');
    }
    public function children()
    {
        return $this->hasMany(Location::class, 'parent_id');
    }



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
        $name = trim($this->name);

        // Special case for "ARB Fashion"
        if (strcasecmp($name, 'ARB Fashion') === 0) {
            return 'AF';
        }

        if (empty($name)) {
            return 'LOC';
        }

        $words = preg_split('/\s+/', $name);
        $prefix = '';

        if (count($words) === 1) {
            $word = strtoupper($words[0]);
            $prefix = substr($word, 0, 3);
            while (strlen($prefix) < 3) {
                $prefix .= '0';
            }
        } else {
            foreach ($words as $word) {
                if (strlen($prefix) >= 3) break;
                if (!empty($word)) {
                    $prefix .= strtoupper(substr($word, 0, 1));
                }
            }
            while (strlen($prefix) < 3) {
                $prefix .= 'X';
            }
        }

        return $prefix;
    }
}
