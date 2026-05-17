<?php

namespace App\Services\Sms;

use App\Models\Setting;
use App\Models\User;

class CustomerSmsOptInPolicy
{
    public static function isSmsFeatureEnabled(): bool
    {
        return (bool) (Setting::first()?->enable_sms ?? false);
    }

    public static function canManageOptIn(?User $user = null): bool
    {
        $user = $user ?? auth()->user();

        if (! $user) {
            return false;
        }

        return static::isSmsFeatureEnabled() && $user->can('sms.send');
    }
}
