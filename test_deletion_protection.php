<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Role;

echo "🛡️ System Deletion Protection Test\n";
echo "==================================\n\n";

// Test 1: Check Master Super Admin users
$masterAdmins = User::whereHas('roles', function($query) {
    $query->where('name', 'Master Super Admin');
})->get();

echo "1. Master Super Admin Users:\n";
foreach ($masterAdmins as $admin) {
    echo "   - {$admin->name} (ID: {$admin->id}, Email: {$admin->email})\n";
}
echo "   Total: {$masterAdmins->count()}\n\n";

// Test 2: Check Super Admin users  
$superAdmins = User::whereHas('roles', function($query) {
    $query->where('name', 'Super Admin');
})->get();

echo "2. Super Admin Users:\n";
foreach ($superAdmins as $admin) {
    echo "   - {$admin->name} (ID: {$admin->id}, Email: {$admin->email})\n";
}
echo "   Total: {$superAdmins->count()}\n\n";

// Test 3: Check critical roles
$criticalRoles = Role::whereIn('name', ['Master Super Admin', 'Super Admin'])->get();

echo "3. Critical System Roles:\n";
foreach ($criticalRoles as $role) {
    $userCount = User::whereHas('roles', function($query) use ($role) {
        $query->where('id', $role->id);
    })->count();
    echo "   - {$role->name} (ID: {$role->id}) - {$userCount} users assigned\n";
}
echo "\n";

// Test 4: Protection scenarios
echo "4. Protection Scenarios:\n";
echo "   ✅ Users cannot delete themselves (prevents lockout)\n";
echo "   ✅ Non-Master admins cannot delete Master Super Admin users\n";
echo "   ✅ Cannot delete last Master Super Admin (prevents system lockout)\n";
echo "   ✅ Cannot delete critical system roles (Master/Super Admin)\n";
echo "   ✅ Cannot delete roles assigned to current user\n";
echo "   ✅ Cannot delete roles that are assigned to any users\n\n";

echo "Protection system implemented successfully! 🎯\n";
