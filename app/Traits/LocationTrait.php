<?php

namespace App\Traits;

use App\Models\Scopes\LocationScope;
use Illuminate\Support\Facades\Auth;

trait LocationTrait{

    // it will insert the location_id using auth user location id to every insert
    protected static function booted(): void
    {
        static::addGlobalScope(new LocationScope);

        static::creating(function ($item) {
             $item->location_id = Auth::user()->location_id;
        });
    }


}
