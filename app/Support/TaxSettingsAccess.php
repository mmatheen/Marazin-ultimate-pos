<?php

namespace App\Support;

use App\Models\User;

class TaxSettingsAccess
{
    public const PERMISSION = 'edit tax-settings';

    /**
     * Strict permission check — no Master Super Admin bypass.
     * User must have "edit tax-settings" assigned on their role.
     */
    public static function canManage(?User $user = null): bool
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return false;
        }

        return $user->hasPermissionTo(self::PERMISSION);
    }
}
