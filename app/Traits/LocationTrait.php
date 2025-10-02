<?php

namespace App\Traits;

use App\Scopes\LocationScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait LocationTrait
{
    protected static $bypassLocationScope = false;

    protected static function booted()
    {
        static::addGlobalScope(new LocationScope);

        static::creating(function ($model) {
            if (empty($model->location_id)) {
                $user = auth()->user();
                if ($user) {
                    try {
                        // Use dynamic property access to avoid static analysis issues
                        if (method_exists($user, 'locations')) {
                            /** @var \Illuminate\Database\Eloquent\Collection $userLocations */
                            $userLocations = call_user_func([$user, 'locations'])->get();
                            if ($userLocations->isNotEmpty()) {
                                $model->location_id = $userLocations->first()->id;
                            }
                        }
                    } catch (\Exception $e) {
                        // If locations method doesn't exist or fails, skip auto-assignment
                        \Illuminate\Support\Facades\Log::debug('LocationTrait: Could not auto-assign location', [
                            'user_id' => $user->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        });
    }

    /**
     * Scope to bypass location scope
     */
    public function scopeWithoutLocationScope($query)
    {
        static::$bypassLocationScope = true;
        return $query;
    }

    /**
     * Check if location scope should be bypassed
     */
    public function shouldBypassLocationScope()
    {
        return (bool) static::$bypassLocationScope;
    }

    /**
     * Reset the bypass flag (optional)
     */
    public static function resetLocationScope()
    {
        static::$bypassLocationScope = false;
    }
}