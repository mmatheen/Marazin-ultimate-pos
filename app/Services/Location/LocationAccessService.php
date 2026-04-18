<?php

namespace App\Services\Location;

use App\Models\Location;
use App\Models\User;
use App\Services\User\UserAccessService;
use Illuminate\Support\Collection;

class LocationAccessService
{
    public function forUser(?User $user): Collection
    {
        if (!$user) {
            return collect([]);
        }

        /** @var UserAccessService $userAccessService */
        $userAccessService = app(UserAccessService::class);

        $isMasterSuperAdmin = $userAccessService->isMasterSuperAdmin($user);
        $hasBypassPermission = $userAccessService->hasLocationBypassPermission($user);

        if ($isMasterSuperAdmin || $hasBypassPermission) {
            return Location::select('id', 'name')->get();
        }

        return Location::select('locations.id', 'locations.name')
            ->join('location_user', 'locations.id', '=', 'location_user.location_id')
            ->where('location_user.user_id', $user->id)
            ->get();
    }
}

