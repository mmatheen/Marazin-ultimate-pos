<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing Master Super Admin vs Super Admin Implementation\n";
echo "======================================================\n\n";

// Check roles
$roles = \Spatie\Permission\Models\Role::all();
echo "Available Roles:\n";
foreach ($roles as $role) {
    echo sprintf("- %s (Key: %s) - System: %s, Master: %s, Bypass Scope: %s\n", 
        $role->name,
        $role->key ?? 'N/A',
        $role->is_system_role ? 'Yes' : 'No',
        $role->is_master_role ? 'Yes' : 'No',
        $role->bypass_location_scope ? 'Yes' : 'No'
    );
}

echo "\n";

// Check Master Admin permissions
$masterAdminPermissions = \Spatie\Permission\Models\Permission::where('group_name', 'LIKE', '%master-admin%')->get();
echo "Master Admin Permissions:\n";
foreach ($masterAdminPermissions as $permission) {
    echo "- " . $permission->name . "\n";
}

echo "\n";

// Check user with Super Admin role
$superAdminUsers = \App\Models\User::role('Super Admin')->first();
if ($superAdminUsers) {
    echo "Testing Super Admin User:\n";
    echo "- ID: " . $superAdminUsers->id . "\n";
    echo "- Name: " . $superAdminUsers->full_name . "\n";
    echo "- Is Master Super Admin: " . ($superAdminUsers->isMasterSuperAdmin() ? 'Yes' : 'No') . "\n";
    echo "- Is Super Admin: " . ($superAdminUsers->isSuperAdmin() ? 'Yes' : 'No') . "\n";
    echo "- Can Bypass Location Scope: " . ($superAdminUsers->canBypassLocationScope() ? 'Yes' : 'No') . "\n";
    echo "- Can Access Master Features: " . ($superAdminUsers->canAccessMasterFeatures() ? 'Yes' : 'No') . "\n";
} else {
    echo "No Super Admin users found.\n";
}

echo "\n";

// Check Master Super Admin role permissions count
$masterSuperAdminRole = \Spatie\Permission\Models\Role::where('name', 'Master Super Admin')->first();
$superAdminRole = \Spatie\Permission\Models\Role::where('name', 'Super Admin')->first();

if ($masterSuperAdminRole) {
    echo "Master Super Admin Role has " . $masterSuperAdminRole->permissions->count() . " permissions\n";
}

if ($superAdminRole) {
    echo "Super Admin Role has " . $superAdminRole->permissions->count() . " permissions\n";
}

echo "\n";

// Check location scope behavior
echo "Location Scope Test:\n";
$user = \App\Models\User::first();
if ($user) {
    echo "- User: " . $user->full_name . "\n";
    echo "- Should Bypass Location Scope: " . ($user->shouldBypassLocationScope() ? 'Yes' : 'No') . "\n";
}

echo "\nImplementation Complete!\n";
