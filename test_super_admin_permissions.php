<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Permission;

// Find Super Admin user
$superAdmin = User::whereHas('roles', function($query) {
    $query->where('name', 'Super Admin');
})->first();

if (!$superAdmin) {
    echo "No Super Admin user found\n";
    exit;
}

echo "Super Admin User: " . $superAdmin->name . "\n";
echo "Email: " . $superAdmin->email . "\n";
echo "ID: " . $superAdmin->id . "\n\n";

echo "Roles:\n";
foreach ($superAdmin->roles as $role) {
    echo "- " . $role->name . " (ID: " . $role->id . ")\n";
}

echo "\nDirect Permissions: " . $superAdmin->permissions->count() . "\n";
if ($superAdmin->permissions->count() > 0) {
    foreach ($superAdmin->permissions as $permission) {
        echo "- " . $permission->name . "\n";
    }
}

echo "\nRole-based Permissions:\n";
foreach ($superAdmin->roles as $role) {
    echo "Role: " . $role->name . " (" . $role->permissions->count() . " permissions)\n";
    foreach ($role->permissions as $permission) {
        echo "  - " . $permission->name . "\n";
    }
}

// Calculate total unique permissions
$directPermissions = $superAdmin->permissions;
$rolePermissions = collect();

foreach ($superAdmin->roles as $role) {
    $rolePermissions = $rolePermissions->merge($role->permissions);
}

$allPermissions = $directPermissions->merge($rolePermissions)->unique('id');

echo "\nTotal Unique Permissions: " . $allPermissions->count() . "\n";
echo "Total System Permissions: " . Permission::count() . "\n";

if ($allPermissions->count() < Permission::count()) {
    echo "\n✅ FILTERING SHOULD WORK - Super Admin has " . $allPermissions->count() . " out of " . Permission::count() . " permissions\n";
} else {
    echo "\n❌ PROBLEM - Super Admin has ALL permissions, no filtering needed\n";
}
