<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    public function handle(Login $event)
    {
        if ($user = $event->user) {
            $properties = [
                'full_name' => $user->user_name ?? ($user->user_name ?? null),
                'email' => $user->email ?? null,
            ];

            if ($user instanceof \Illuminate\Database\Eloquent\Model) {
                activity('auth')
                    ->causedBy($user)
                    ->performedOn($user)
                    ->withProperties($properties)
                    ->log('Logged in');
            } else {
                $properties['user_id'] = $user->getAuthIdentifier();
                activity('auth')
                    ->withProperties($properties)
                    ->log('Logged in');
            }
        }
    }
}
