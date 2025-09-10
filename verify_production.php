<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

echo "🎯 Production Database Verification\n";
echo "==================================\n\n";

// Count statistics
$userCount = User::count();
$roleCount = Role::count();
$permissionCount = Permission::count();

echo "📊 Database Statistics:\n";
echo "  - Users: {$userCount}\n";
echo "  - Roles: {$roleCount}\n";
echo "  - Permissions: {$permissionCount}\n\n";

// Check Master Super Admin
$masterAdmin = User::where('email', 'masteradmin@gmail.com')->with('roles')->first();
echo "👑 Master Super Admin:\n";
if ($masterAdmin) {
    $roleName = $masterAdmin->roles->first() ? $masterAdmin->roles->first()->name : 'No Role';
    echo "  ✅ Found: {$masterAdmin->full_name} ({$masterAdmin->email})\n";
    echo "  🎭 Role: {$roleName}\n";
} else {
    echo "  ❌ Not Found\n";
}

// Check Super Admin
$superAdmin = User::where('email', 'admin@gmail.com')->with('roles')->first();
echo "\n🚀 Super Admin:\n";
if ($superAdmin) {
    $roleName = $superAdmin->roles->first() ? $superAdmin->roles->first()->name : 'No Role';
    echo "  ✅ Found: {$superAdmin->full_name} ({$superAdmin->email})\n";
    echo "  🎭 Role: {$roleName}\n";
} else {
    echo "  ❌ Not Found\n";
}

// Check critical roles
echo "\n🔑 Critical Roles Check:\n";
$masterRole = Role::where('name', 'Master Super Admin')->first();
$superRole = Role::where('name', 'Super Admin')->first();

echo "  - Master Super Admin Role: " . ($masterRole ? "✅ Exists" : "❌ Missing") . "\n";
echo "  - Super Admin Role: " . ($superRole ? "✅ Exists" : "❌ Missing") . "\n";

if ($masterRole) {
    echo "  - Master Super Admin Permissions: {$masterRole->permissions->count()}\n";
}
if ($superRole) {
    echo "  - Super Admin Permissions: {$superRole->permissions->count()}\n";
}

echo "\n🎉 Production seeding verification completed!\n";
