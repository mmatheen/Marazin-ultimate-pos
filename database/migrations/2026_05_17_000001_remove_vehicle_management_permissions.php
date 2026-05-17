<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const RETIRED_PERMISSION_NAMES = [
        'view vehicles',
        'create vehicle',
        'edit vehicle',
        'delete vehicle',
        'track vehicle',
        'assign vehicle to location',
    ];

    public function up(): void
    {
        $permissionIds = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', self::RETIRED_PERMISSION_NAMES)
            ->pluck('id');

        if ($permissionIds->isEmpty()) {
            return;
        }

        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }

    public function down(): void
    {
        $group = '32. vehicle-management';
        $guard = 'web';

        foreach (self::RETIRED_PERMISSION_NAMES as $name) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name, 'guard_name' => $guard],
                ['group_name' => $group, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
};
