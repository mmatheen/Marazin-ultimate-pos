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