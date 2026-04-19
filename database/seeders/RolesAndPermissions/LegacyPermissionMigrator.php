<?php

namespace Database\Seeders\RolesAndPermissions;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Removes duplicate permission rows and migrates legacy group/permission names.
 * DB-heavy; mappings live in database/seeders/Data/legacy_permission_cleanup.php.
 */
final class LegacyPermissionMigrator
{
    public function removeDuplicatePermissionRows(Command $command): void
    {
        $duplicatePermissions = DB::table('permissions')
            ->select('name', 'guard_name', DB::raw('COUNT(*) as count'))
            ->groupBy('name', 'guard_name')
            ->having('count', '>', 1)
            ->get();

        foreach ($duplicatePermissions as $duplicate) {
            $permissionsToDelete = DB::table('permissions')
                ->where('name', $duplicate->name)
                ->where('guard_name', $duplicate->guard_name)
                ->orderBy('id')
                ->skip(1)
                ->pluck('id');

            if ($permissionsToDelete->count() === 0) {
                continue;
            }

            DB::table('role_has_permissions')->whereIn('permission_id', $permissionsToDelete)->delete();
            DB::table('model_has_permissions')->whereIn('permission_id', $permissionsToDelete)->delete();
            DB::table('permissions')->whereIn('id', $permissionsToDelete)->delete();

            $command->warn("Removed duplicate permissions for: {$duplicate->name}");
        }
    }

    public function runLegacyRenamesAndMerges(Command $command): void
    {
        /** @var array{group_name_renames: array<string, string>, permission_name_migrations: array<string, string>, duplicate_canonical_merge: array<string, string>} $data */
        $data = require dirname(__DIR__) . '/Data/legacy_permission_cleanup.php';

        foreach ($data['group_name_renames'] as $oldGroup => $newGroup) {
            $updated = DB::table('permissions')
                ->where('group_name', $oldGroup)
                ->update(['group_name' => $newGroup]);

            if ($updated > 0) {
                $command->info("Updated {$updated} permissions from '{$oldGroup}' to '{$newGroup}'");
            }
        }

        foreach ($data['permission_name_migrations'] as $oldName => $newName) {
            $this->migrateOrMergePermissionName($oldName, $newName, $command);
        }

        foreach ($data['duplicate_canonical_merge'] as $oldName => $newName) {
            $this->mergePermissions($oldName, $newName, $command);
        }
    }

    private function migrateOrMergePermissionName(string $oldName, string $newName, Command $command): void
    {
        $newExists = DB::table('permissions')
            ->where('name', $newName)
            ->where('guard_name', 'web')
            ->exists();

        $oldExists = DB::table('permissions')
            ->where('name', $oldName)
            ->where('guard_name', 'web')
            ->exists();

        if ($oldExists && $newExists) {
            $oldPermissionId = DB::table('permissions')
                ->where('name', $oldName)
                ->where('guard_name', 'web')
                ->value('id');

            $newPermissionId = DB::table('permissions')
                ->where('name', $newName)
                ->where('guard_name', 'web')
                ->value('id');

            $roleAssignments = DB::table('role_has_permissions')
                ->where('permission_id', $oldPermissionId)
                ->select('role_id')
                ->get();

            foreach ($roleAssignments as $assignment) {
                $exists = DB::table('role_has_permissions')
                    ->where('role_id', $assignment->role_id)
                    ->where('permission_id', $newPermissionId)
                    ->exists();

                if (! $exists) {
                    DB::table('role_has_permissions')->insert([
                        'role_id' => $assignment->role_id,
                        'permission_id' => $newPermissionId,
                    ]);
                }
            }

            DB::table('role_has_permissions')->where('permission_id', $oldPermissionId)->delete();

            $modelAssignments = DB::table('model_has_permissions')
                ->where('permission_id', $oldPermissionId)
                ->select('model_type', 'model_id')
                ->get();

            foreach ($modelAssignments as $assignment) {
                $exists = DB::table('model_has_permissions')
                    ->where('model_type', $assignment->model_type)
                    ->where('model_id', $assignment->model_id)
                    ->where('permission_id', $newPermissionId)
                    ->exists();

                if (! $exists) {
                    DB::table('model_has_permissions')->insert([
                        'permission_id' => $newPermissionId,
                        'model_type' => $assignment->model_type,
                        'model_id' => $assignment->model_id,
                    ]);
                }
            }

            DB::table('model_has_permissions')->where('permission_id', $oldPermissionId)->delete();
            DB::table('permissions')->where('id', $oldPermissionId)->delete();

            $command->info("Merged permission '{$oldName}' into '{$newName}'");
        } elseif ($oldExists && ! $newExists) {
            DB::table('permissions')
                ->where('name', $oldName)
                ->where('guard_name', 'web')
                ->update(['name' => $newName]);

            $command->info("Renamed permission from '{$oldName}' to '{$newName}'");
        }
    }

    private function mergePermissions(string $oldName, string $newName, Command $command): void
    {
        $oldPermission = DB::table('permissions')
            ->where('name', $oldName)
            ->where('guard_name', 'web')
            ->first();

        $newPermission = DB::table('permissions')
            ->where('name', $newName)
            ->where('guard_name', 'web')
            ->first();

        if ($oldPermission && $newPermission) {
            $roleAssignmentsToMove = DB::table('role_has_permissions as rhp1')
                ->where('rhp1.permission_id', $oldPermission->id)
                ->whereNotExists(function ($query) use ($newPermission) {
                    $query->select('*')
                        ->from('role_has_permissions as rhp2')
                        ->where('rhp2.permission_id', $newPermission->id)
                        ->whereRaw('rhp2.role_id = rhp1.role_id');
                })
                ->pluck('role_id');

            foreach ($roleAssignmentsToMove as $roleId) {
                DB::table('role_has_permissions')
                    ->where('permission_id', $oldPermission->id)
                    ->where('role_id', $roleId)
                    ->update(['permission_id' => $newPermission->id]);
            }

            DB::table('role_has_permissions')
                ->where('permission_id', $oldPermission->id)
                ->delete();

            $modelAssignmentsToMove = DB::table('model_has_permissions as mhp1')
                ->where('mhp1.permission_id', $oldPermission->id)
                ->whereNotExists(function ($query) use ($newPermission) {
                    $query->select('*')
                        ->from('model_has_permissions as mhp2')
                        ->where('mhp2.permission_id', $newPermission->id)
                        ->whereRaw('mhp2.model_type = mhp1.model_type')
                        ->whereRaw('mhp2.model_id = mhp1.model_id');
                })
                ->select(['model_type', 'model_id'])
                ->get();

            foreach ($modelAssignmentsToMove as $assignment) {
                DB::table('model_has_permissions')
                    ->where('permission_id', $oldPermission->id)
                    ->where('model_type', $assignment->model_type)
                    ->where('model_id', $assignment->model_id)
                    ->update(['permission_id' => $newPermission->id]);
            }

            DB::table('model_has_permissions')
                ->where('permission_id', $oldPermission->id)
                ->delete();

            DB::table('permissions')->where('id', $oldPermission->id)->delete();

            $command->info("Merged permission '{$oldName}' into '{$newName}'");
        } elseif ($oldPermission && ! $newPermission) {
            DB::table('permissions')
                ->where('id', $oldPermission->id)
                ->update(['name' => $newName]);

            $command->info("Renamed permission from '{$oldName}' to '{$newName}'");
        }
    }
}
