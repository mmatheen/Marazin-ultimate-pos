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
            $user = Auth::user();
            $selectedLocation = Session::get('selected_location');

            if ($selectedLocation) {
                // Filter by selected location or where location_id is null
                $builder->where(function ($query) use ($selectedLocation) {
                    $query->where('location_id', $selectedLocation)
                        ->orWhereNull('location_id');
                });
            } else {
                $locationIds = $user->locations->pluck('id')->toArray();
                $builder->where(function ($query) use ($locationIds) {
                    $query->whereIn('location_id', $locationIds)
                        ->orWhereNull('location_id');
                });
            }

            // Add user_id based filtering
            $builder->where('user_id', $user->id);
        }
    }
}
