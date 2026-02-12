<?php

namespace App\Http\Helpers;

use App\Models\Location;
use Illuminate\Support\Facades\Auth;

class Common
{
    public static function getLocationId(){
        if(Auth::user()->role_name === null){
            // Check both session keys for backwards compatibility
            if(session()->has('selected_location')){
                return session()->get('selected_location');
            }elseif(session()->has('selectedLocation')){
                return session()->get('selectedLocation');
            }else{
                return Location::first()->id;
            }
        }else{
            return Auth::user()->location_id;
        }
    }

}
