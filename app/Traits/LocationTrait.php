<?php

namespace App\Traits;

use App\Scopes\LocationScope;
use Illuminate\Support\Facades\Auth;

trait LocationTrait
{
    protected $bypassLocationScope = false;

    protected static function booted(): void
    {
        static::addGlobalScope(new LocationScope);

        static::creating(function ($model) {
            if (empty($model->location_id)) {
                $firstLocation = Auth::user()?->locations->first();
                $model->location_id = $firstLocation?->id;
            }
        });
    }

    /**
     * Temporarily disable location scope for this query
     */
    public function scopeWithoutLocationScope($query)
    {
        $this->bypassLocationScope = true;
        return $query;
    }

    /**
     * Helper to check if scope should be bypassed
     */
    public function shouldBypassLocationScope(): bool
    {
        return (bool) $this->bypassLocationScope;
    }

    /**
     * Filter by specific location (optional helper)
     */
    public function scopeByLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }
}
