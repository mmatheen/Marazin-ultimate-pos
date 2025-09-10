<?php

use Illuminate\Support\Facades\Route;

Route::get('/debug-permissions', function() {
    if (!auth()->check()) {
        return response()->json(['error' => 'Not authenticated']);
    }
    
    $user = auth()->user();
    
    $data = [
        'user_info' => [
            'name' => $user->full_name,
            'email' => $user->email,
            'roles' => $user->roles->pluck('name')->toArray(),
        ],
        'user_has_permissions' => $user->getAllPermissions()->map(function($p) {
            return ['id' => $p->id, 'name' => $p->name, 'group' => $p->group_name];
        })->toArray(),
        'all_permissions_in_system' => \Spatie\Permission\Models\Permission::all()->map(function($p) {
            return ['id' => $p->id, 'name' => $p->name, 'group' => $p->group_name];
        })->toArray(),
        'is_master_super_admin' => $user->hasRole('Master Super Admin'),
        'user_permissions_count' => $user->getAllPermissions()->count(),
        'total_permissions_count' => \Spatie\Permission\Models\Permission::count(),
    ];
    
    return response()->json($data, 200, [], JSON_PRETTY_PRINT);
})->middleware('auth')->name('debug.permissions');
