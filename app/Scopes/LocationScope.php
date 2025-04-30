<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class LocationScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (Auth::check() && !Auth::user()->hasRole('Super Admin')) {
            // Check if user has selected a specific location
            $selectedLocation = Session::get('selected_location');

            if ($selectedLocation) {
                // Only show data for the selected location
                $builder->where('location_id', $selectedLocation);
            } else {
                // If not selected, use all accessible locations
                $user = Auth::user();
                $locationIds = $user->locations->pluck('id')->toArray();
                $builder->whereIn('location_id', $locationIds);
            }
        }
    }
    
}
