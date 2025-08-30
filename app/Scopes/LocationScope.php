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
        if (Auth::check() && !(Auth::user()->role === 'Super Admin')) {
            // Works for both Breeze and Sanctum as long as the correct authentication middleware is applied
            $user = Auth::user();
            $selectedLocation = Session::get('selected_location');

            if ($selectedLocation) {
                // எல்லா users-ன் data-வும் show ஆகும்
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

        //   // Add user_id based filtering only if the table has user_id column
        //     if (in_array('user_id', $builder->getModel()->getConnection()->getSchemaBuilder()->getColumnListing($builder->getModel()->getTable()))) {
        //         $builder->where('user_id', $user->id);
        //     }
            
            
        }
    }
}