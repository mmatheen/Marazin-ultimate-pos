<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait LocationTrait
{
    public static function bootLocationTrait()
    {
        static::creating(function ($model) {
            if (!$model->location_id) {
                $model->location_id = Auth::user()->location_id ?? 1; // Default Location ID
            }
        });

        static::updating(function ($model) {
            if (!$model->location_id) {
                $model->location_id = Auth::user()->location_id;
            }
        });
    }
}
