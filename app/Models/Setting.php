<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_name',
        'logo',
        'favicon',
        'enable_price_validation',
        'enable_free_qty',
        'default_tax_percent',
        'default_selling_price_tax_type',
        'sms_user_id',
        'sms_api_key',
        'sms_sender_id',
    ];

    protected $casts = [
        'default_tax_percent' => 'float',
        'sms_api_key' => 'encrypted',
        'enable_price_validation' => 'boolean',
        'enable_free_qty' => 'boolean',
    ];

    // Accessor: Full URL for logo
    public function getLogoUrlAttribute()
    {
        return $this->logo ? Storage::url('settings/' . $this->logo) : asset('assets/img/MARAZIN.png');
    }

    // Accessor: Full URL for favicon
    public function getFaviconUrlAttribute()
    {
        if ($this->favicon) {
            return asset('storage/settings/' . $this->favicon);
        }
        return asset('assets/img/favicon.png'); // Fallback favicon
    }
}
