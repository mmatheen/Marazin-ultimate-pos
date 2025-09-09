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
        'logo_image',
        'parent_id',
        'vehicle_number',
        'vehicle_type',
    ];

    protected $appends = ['logo_url'];

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

    /**
     * Get the logo URL attribute
     */
    public function getLogoUrlAttribute()
    {
        if ($this->logo_image) {
            return asset($this->logo_image);
        }
        return null;
    }

    /**
     * Check if this location is a parent location (no parent_id)
     */
    public function isParentLocation()
    {
        return is_null($this->parent_id);
    }

    /**
     * Check if this location is a sublocation (has parent_id)
     */
    public function isSublocation()
    {
        return !is_null($this->parent_id);
    }

    /**
     * Check if this location has valid vehicle details
     */
    public function hasVehicleDetails()
    {
        return !empty($this->vehicle_number) && !empty($this->vehicle_type);
    }

    /**
     * Validate vehicle requirements for sublocations
     */
    public function validateVehicleRequirements()
    {
        if ($this->isSublocation() && !$this->hasVehicleDetails()) {
            throw new \InvalidArgumentException('Sublocations must have vehicle_number and vehicle_type.');
        }
        
        return true;
    }

    /**
     * Get all descendant locations (sublocations recursively)
     */
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    /**
     * Boot method to add model events
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($location) {
            $location->validateVehicleRequirements();
        });
    }
}
