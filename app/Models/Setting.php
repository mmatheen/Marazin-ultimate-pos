<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_name',
        'logo',
        'favicon',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Ensure only one active setting
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($setting) {
            if ($setting->is_active) {
                // Deactivate all others
                self::where('id', '!=', $setting->id)->update(['is_active' => false]);
            } else {
                // If no active setting exists, make this one active
                if (!self::where('is_active', true)->exists() && !$setting->exists) {
                    $setting->is_active = true;
                }
            }
        });

        static::saved(function ($setting) {
            // Clear cache whenever a setting is saved
            Cache::forget('active_setting');
        });

        static::deleted(function ($setting) {
            // Clear cache whenever a setting is deleted
            Cache::forget('active_setting');
        });
    }

    // Accessor: Logo URL with fallback
    public function getLogoUrlAttribute()
    {
        if ($this->logo) {
            return asset('storage/settings/' . $this->logo);
        }
        return asset('assets/img/ARB Logo.png'); // fallback
    }

    // Accessor: Favicon URL
    public function getFaviconUrlAttribute()
    {
        if ($this->favicon) {
            return asset('storage/settings/' . $this->favicon);
        }
        return asset('favicon.ico'); // fallback
    }
}
