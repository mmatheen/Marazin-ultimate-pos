<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionsDemoController extends Controller
{
    function __construct()
    {
        // Demo controller - minimal permissions needed
        $this->middleware('auth');
    }

    public function index()
    {
        return view('demo.permissions_demo');
    }

    public function testPermissionsAjax(Request $request)
    {
        $user = auth()->user();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Permission system is working!',
            'user_role' => $user->roles->pluck('name'),
            'permissions_count' => $user->getAllPermissions()->count(),
            'total_permissions' => Permission::count(),
            'timestamp' => now()->toDateTimeString()
        ]);
    }
}
