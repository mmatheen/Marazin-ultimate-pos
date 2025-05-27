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
            $selectedLocation = Session::get('selected_location');

            if ($selectedLocation) {
                // Show data for the selected location or where location_id is null (walking customer)
                $builder->where(function ($query) use ($selectedLocation) {
                    $query->where('location_id', $selectedLocation)
                        ->orWhereNull('location_id');
                });
            } else {
                $user = Auth::user();
                $locationIds = $user->locations->pluck('id')->toArray();
                $builder->where(function ($query) use ($locationIds) {
                    $query->whereIn('location_id', $locationIds)
                        ->orWhereNull('location_id');
                });
            }
        }
    }
}
