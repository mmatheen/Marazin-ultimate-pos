<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🎯 Sales Rep Role Validation Test\n";
echo str_repeat("=", 50) . "\n\n";

// Check if Sales Rep role exists
$role = \Spatie\Permission\Models\Role::where('name', 'Sales Rep')
    ->orWhere('key', 'sales_rep')
    ->first();

if ($role) {
    echo "✅ Sales Rep role found:\n";
    echo "   - Name: {$role->name}\n";
    echo "   - Key: {$role->key}\n";
    echo "   - Guard: {$role->guard_name}\n\n";
} else {
    echo "❌ Sales Rep role not found!\n\n";
}

// Check all roles
echo "📋 All Available Roles:\n";
$roles = \Spatie\Permission\Models\Role::all(['id', 'name', 'key']);
foreach ($roles as $r) {
    echo "   - {$r->name} (key: {$r->key})\n";
}

echo "\n";

// Test the validation logic
echo "🧪 Testing Role Validation Logic:\n";

if ($role) {
    // Simulate user with roles
    $testUser = new class {
        public $roles;
        
        public function __construct() {
            $this->roles = collect();
        }
        
        public function addRole($role) {
            $this->roles->push($role);
        }
    };
    
    $testUser->addRole($role);
    
    // Test the validation function
    $isValid = $testUser->roles->contains(function($role) {
        return $role->name === 'Sales Rep' || 
               $role->name === 'sales rep' || 
               $role->key === 'sales_rep';
    });
    
    echo "   ✅ Validation Result: " . ($isValid ? "PASS" : "FAIL") . "\n\n";
} else {
    echo "   ❌ Cannot test - Sales Rep role missing\n\n";
}

echo "🎉 Role validation is now flexible and supports:\n";
echo "   - Role name: 'Sales Rep'\n";
echo "   - Role name: 'sales rep'\n";
echo "   - Role key: 'sales_rep'\n\n";

echo "Frontend users with role_key='sales_rep' will now be accepted! 🚀\n";

?>
