<?php

// namespace App\Scopes;

// use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\Scope;
// use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\Session;

// class LocationScope implements Scope
// {
//     public function apply(Builder $builder, Model $model)
//     {
//         if (Auth::check() && !Auth::user()->hasRole('Super Admin')) {
//             $user = Auth::user();
//             $selectedLocation = Session::get('selected_location');

//             if ($selectedLocation) {
//                 // Filter by selected location or where location_id is null
//                 $builder->where(function ($query) use ($selectedLocation) {
//                     $query->where('location_id', $selectedLocation)
//                         ->orWhereNull('location_id');
//                 });
//             } else {
//                 $locationIds = $user->locations->pluck('id')->toArray();
//                 $builder->where(function ($query) use ($locationIds) {
//                     $query->whereIn('location_id', $locationIds)
//                         ->orWhereNull('location_id');
//                 });
//             }

//             // Add user_id based filtering only if the table has user_id column
//             if (in_array('user_id', $builder->getModel()->getConnection()->getSchemaBuilder()->getColumnListing($builder->getModel()->getTable()))) {
//                 $builder->where('user_id', $user->id);
//             }
//         }
//     }
// }

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
        // Skip scope if bypass is requested
        if (method_exists($model, 'shouldBypassLocationScope') && $model->shouldBypassLocationScope()) {
            return;
        }

        $user = Auth::user();

        // No filter if not logged in or is Super Admin
        if (!Auth::check() || ($user && $user->hasRole('Super Admin'))) {
            return;
        }

        $selectedLocation = Session::get('selected_location');
        $locationIds = $user->locations->pluck('id')->toArray();

        // Apply location filter: selected or assigned locations
        $builder->where(function ($query) use ($selectedLocation, $locationIds) {
            if ($selectedLocation) {
                $query->where('location_id', $selectedLocation);
            } else {
                $query->whereIn('location_id', $locationIds);
            }
            // Allow records with no location (e.g., walk-in)
            $query->orWhereNull('location_id');
        });

        // Add user_id filter only if the table has the column
        $table = $builder->getModel()->getTable();
        $columns = $builder->getModel()->getConnection()->getSchemaBuilder()->getColumnListing($table);

        if (in_array('user_id', $columns)) {
            $builder->where('user_id', $user->id);
        }
    }
}