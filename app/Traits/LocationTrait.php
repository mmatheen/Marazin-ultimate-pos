<?php

namespace App\Traits;

use App\Scopes\LocationScope;

use Illuminate\Support\Facades\Auth;

trait LocationTrait{

    // it will insert the location_id using auth user location id to every insert
    protected static function booted(): void
    {
        static::addGlobalScope(new LocationScope);

        static::creating(function ($model) {
            if (empty($model->location_id)) {
                // If multiple locations, decide how to default:
                // You can take first accessible location or pass from request
                $model->location_id = Auth::user()->locations->first()->id ?? null;
            }
        });
    }

}
