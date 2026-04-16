<?php

namespace App\Http\Helpers;

use App\Models\Location;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class Common
{
    public static function getLocationId(){
        // Canonical session key
        if (session()->has('selected_location')) {
            return session()->get('selected_location');
        }

        // Backward compatibility for legacy key; migrate to canonical key immediately
        if (session()->has('selectedLocation')) {
            $legacyLocationId = session()->get('selectedLocation');
            session()->put('selected_location', $legacyLocationId);
            session()->forget('selectedLocation');

            return $legacyLocationId;
        }

        // Fallback: if logged in and has assigned locations, use first one
        /** @var User|null $authUser */
        $authUser = Auth::user();
        if ($authUser instanceof User) {
            $firstAssignedLocation = $authUser->locations()->orderBy('locations.id')->value('locations.id');
            if ($firstAssignedLocation) {
                return $firstAssignedLocation;
            }
        }

        // Final fallback for legacy flows
        return Location::query()->orderBy('id')->value('id');
    }

}
