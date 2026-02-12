<?php

namespace App\Traits;

use App\Scopes\LocationScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait LocationTrait
{
    /**
     * Per-instance bypass flag. This is an instance property (not static)
     * so bypassing on one model query does NOT leak into other queries
     * on the same model class within the same request.
     */
    public bool $bypassLocationScope = false;

    protected static function booted()
    {
        static::addGlobalScope(new LocationScope);

        static::creating(function ($model) {
            if (empty($model->location_id)) {
                $user = auth()->user();
                if ($user) {
                    try {
                        if (method_exists($user, 'locations')) {
                            $userLocations = call_user_func([$user, 'locations'])->get();
                            if ($userLocations->isNotEmpty()) {
                                $model->location_id = $userLocations->first()->id;
                            }
                        }
                    } catch (\Exception $e) {
                        // Skip auto-assignment on failure
                    }
                }
            }
        });
    }

    /**
     * Scope to bypass location scope for THIS query only.
     * Uses Eloquent's built-in withoutGlobalScope() so the bypass
     * is query-scoped and automatically cleaned up.
     *
     * Usage: Model::withoutLocationScope()->where(...)->get()
     */
    public function scopeWithoutLocationScope($query)
    {
        return $query->withoutGlobalScope(LocationScope::class);
    }

    /**
     * Check if location scope should be bypassed for this model instance.
     */
    public function shouldBypassLocationScope(): bool
    {
        return $this->bypassLocationScope;
    }
}