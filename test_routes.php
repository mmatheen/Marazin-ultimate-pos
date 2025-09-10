<?php

use Illuminate\Support\Facades\Route;

Route::get('/test-hierarchy-simple', function() {
    if (!auth()->check()) {
        return response()->json(['error' => 'Not authenticated']);
    }
    
    $user = auth()->user();
    
    try {
        $data = [
            'current_user' => [
                'name' => $user->full_name,
                'email' => $user->email,
                'role' => $user->roles->first()->name ?? 'No Role',
                'is_master_super_admin' => $user->isMasterSuperAdmin(),
                'is_super_admin' => $user->isSuperAdmin(),
                'can_bypass_location_scope' => $user->canBypassLocationScope(),
            ],
            'visible_roles' => $user->getVisibleRoles()->map(function($role) {
                return [
                    'name' => $role->name,
                    'is_system_role' => $role->is_system_role ?? false,
                    'is_master_admin' => $role->is_master_admin ?? false,
                ];
            }),
            'assignable_roles' => $user->getAssignableRoles()->map(function($role) {
                return ['name' => $role->name];
            }),
            'visible_users_count' => $user->getVisibleUsers()->count(),
        ];
        
        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
        
    } catch (Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
})->middleware('auth')->name('test.hierarchy.simple');
