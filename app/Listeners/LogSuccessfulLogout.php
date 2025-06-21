<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

class LogSuccessfulLogout
{
    public function handle(Logout $event)
    {
        if ($user = $event->user) {
            activity('auth')
                ->causedBy(method_exists($user, 'getKey') ? $user : ($user->id ?? null))
                ->performedOn($user instanceof \Illuminate\Database\Eloquent\Model ? $user : null)
                ->withProperties([
                    'user_name' => $user->user_name ?? null,
                    'email' => $user->email ?? null,
                ])
                ->log('Logged out');
        }
    }
}
