<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class LocationScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        // Check if user is authenticated and is not an admin
        if (Auth::check() && !Auth::user()->is_admin) {
            // Filter by location_id if user is not admin
            $builder->where('location_id', Auth::user()->location_id);
        }
    }
}
