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
        'invoice_layout_pos',
        'footer_note',
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

        // Remove special characters and extra spaces, keep only letters and numbers
        $cleanName = preg_replace('/[^a-zA-Z0-9\s]/', '', $name);
        $words = preg_split('/\s+/', trim($cleanName));
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

    // Add constants for layout types
    const LAYOUT_80MM = '80mm';
    const LAYOUT_A4 = 'a4';
    const LAYOUT_DOT_MATRIX = 'dot_matrix';
    const LAYOUT_DOT_MATRIX_FULL = 'dot_matrix_full';

    /**
     * Get the receipt view name based on layout
     */
    public function getReceiptViewName()
    {
        switch ($this->invoice_layout_pos) {
            case self::LAYOUT_A4:
                return 'sell.receipt_a4';
            case self::LAYOUT_DOT_MATRIX:
                return 'sell.receipt_dot_matrix';
            case self::LAYOUT_DOT_MATRIX_FULL:
                return 'sell.receipt_dot_matrix_full';
            case self::LAYOUT_80MM:
            default:
                return 'sell.receipt'; // Default 80mm thermal
        }
    }

    /**
     * Get available layout options for form selection
     */
    public static function getLayoutOptions()
    {
        return [
            self::LAYOUT_80MM => '80mm Thermal Printer',
            self::LAYOUT_A4 => 'A4 Size Printer',
            self::LAYOUT_DOT_MATRIX => 'Dot Matrix Printer (Half - 5.5in)',
            self::LAYOUT_DOT_MATRIX_FULL => 'Dot Matrix Printer (Full - 11in)'
        ];
    }

    /**
     * Get the layout display name
     */
    public function getLayoutDisplayName()
    {
        $options = self::getLayoutOptions();
        return $options[$this->invoice_layout_pos] ?? 'Unknown Layout';
    }
}
