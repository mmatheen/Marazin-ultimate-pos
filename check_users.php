<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

use App\Models\User;

echo "Current Users:\n";
echo "=============\n";

$users = User::with('roles')->get();

foreach ($users as $user) {
    $role = $user->roles->first();
    echo "• " . $user->full_name . " (" . $user->email . ") - Role: " . ($role ? $role->name : 'No Role') . "\n";
}

echo "\nTotal users: " . $users->count() . "\n";
