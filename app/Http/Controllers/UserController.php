<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:view user', ['only' => ['index', 'show', 'user']]);
        $this->middleware('permission:create user', ['only' => ['store']]);
        $this->middleware('permission:edit user', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete user', ['only' => ['destroy']]);
        $this->middleware('role.security', ['only' => ['edit', 'update', 'destroy']]);
    }

    public function user()
    {
        return view('user.user');
    }

    // app/Http/Controllers/UserController.php

    public function index()
    {
        // Get users that current user can see based on hierarchy
        $user = auth()->user();
        $isMasterSuperAdmin = $user->roles->where('name', 'Master Super Admin')->count() > 0;
        
        if ($isMasterSuperAdmin) {
            $users = User::with(['roles', 'locations'])->get();
        } else {
            // Non-Master Super Admin users cannot see Master Super Admin users
            $users = User::whereDoesntHave('roles', function($query) {
                $query->where('name', 'Master Super Admin');
            })->with(['roles', 'locations'])->get();
        }

        if ($users->isNotEmpty()) {
            return response()->json([
                'status' => 200,
                'message' => $users->map(function ($user) {
                    $role = $user->roles->first(); // Assume one role per user (or pick primary)
                    return [
                        'id' => $user->id,
                        'name_title' => $user->name_title,
                        'full_name' => $user->full_name,
                        'user_name' => $user->user_name,
                        'email' => $user->email,
                        'role' => $role?->name ?? '—',        // Display name
                        'role_key' => $role?->key ?? '—',     // Canonical key
                        'locations' => $user->locations->pluck('name')->toArray(),
                    ];
                }),
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'No Records Found!',
            ]);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name_title' => 'required|string|max:10',
                'full_name' => 'required|string',
                'user_name' => 'required|string|max:50|unique:users,user_name',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:5|confirmed',
                'roles' => 'required|string|exists:roles,name',
                'location_id' => 'required|array',
                'location_id.*' => 'exists:locations,id',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        }

        $user = User::create([
            'name_title' => $request->name_title,
            'full_name' => $request->full_name,
            'user_name' => $request->user_name,
            'email' => $request->email,
            'role_name' => $request->roles,
            'password' => bcrypt($request->password),
        ]);

        $user->assignRole($request->roles);
        
        // Validate and sync locations
        $this->validateAndSyncUserLocations($user, $request->location_id ?? []);

        if ($user) {
            return response()->json([
                'status' => 200,
                'message' => "New User Details Created Successfully!"
            ]);
        } else {
            return response()->json([
                'status' => 500,
                'message' => "Something went wrong!"
            ]);
        }
    }

    public function show(int $id)
    {
        $user = User::with(['roles', 'locations'])->find($id);

        if ($user) {
            return response()->json([
                'status' => 200,
                'message' => [
                    'id' => $user->id,
                    'name_title' => $user->name_title,
                    'full_name' => $user->full_name,
                    'user_name' => $user->getRoleNames()->first(),
                    'email' => $user->email,
                    'role' => $user->getRoleNames()->first(),
                    'location_ids' => $user->locations->pluck('id')->toArray(),
                    'locations' => $user->locations->pluck('name')->toArray(),
                ]
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Such User Found!"
            ]);
        }
    }

    public function edit(int $id)
    {
        $currentUser = auth()->user();
        $user = User::with(['roles', 'locations'])->find($id);

        if (!$user) {
            return response()->json([
                'status' => 404,
                'message' => "No Such User Found!"
            ]);
        }

        $currentUserIsMasterSuperAdmin = $currentUser->roles->where('name', 'Master Super Admin')->count() > 0;
        $currentUserIsSuperAdmin = $currentUser->roles->where('key', 'super_admin')->count() > 0;
        $targetUserIsMasterSuperAdmin = $user->roles->where('name', 'Master Super Admin')->count() > 0;
        $targetUserIsSuperAdmin = $user->roles->where('key', 'super_admin')->count() > 0;

        // Prevent non-Master Super Admin users from editing Master Super Admin users
        if ($targetUserIsMasterSuperAdmin && !$currentUserIsMasterSuperAdmin) {
            return response()->json([
                'status' => 403,
                'message' => "You do not have permission to edit Master Super Admin users."
            ], 403);
        }

        // Allow super admin and master admin to edit their own profile
        // This will be handled in the update method for role restrictions
        $isOwnProfile = $currentUser->id === $user->id;
        
        return response()->json([
            'status' => 200,
            'message' => [
                'id' => $user->id,
                'name_title' => $user->name_title,
                'full_name' => $user->full_name,
                'user_name' => $user->user_name,
                'email' => $user->email,
                'role' => $user->roles->first()?->name ?? 'No Role',
                'location_ids' => $user->locations->pluck('id')->toArray(),
                'locations' => $user->locations->pluck('name')->toArray(),
                'is_own_profile' => $isOwnProfile,
                'can_edit_role' => !$isOwnProfile || (!$currentUserIsMasterSuperAdmin && !$currentUserIsSuperAdmin)
            ]
        ]);
    }

    public function update(Request $request, int $id)
    {
        $currentUser = auth()->user();
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => 404,
                'message' => "No Such User Found!"
            ]);
        }

        $currentUserIsMasterSuperAdmin = $currentUser->roles->where('name', 'Master Super Admin')->count() > 0;
        $currentUserIsSuperAdmin = $currentUser->roles->where('key', 'super_admin')->count() > 0;
        $targetUserIsMasterSuperAdmin = $user->roles->where('name', 'Master Super Admin')->count() > 0;
        $targetUserIsSuperAdmin = $user->roles->where('key', 'super_admin')->count() > 0;
        $isOwnProfile = $currentUser->id === $user->id;

        // Prevent non-Master Super Admin users from editing Master Super Admin users
        if ($targetUserIsMasterSuperAdmin && !$currentUserIsMasterSuperAdmin) {
            return response()->json([
                'status' => 403,
                'message' => "You do not have permission to edit Master Super Admin users."
            ], 403);
        }

        // Check if user is trying to change their own role
        $currentRole = $user->roles->first()?->name;
        $isChangingOwnRole = $isOwnProfile && $request->roles && $request->roles !== $currentRole;
        
        if ($isChangingOwnRole) {
            return response()->json([
                'status' => 403,
                'message' => "You cannot change your own role. Please contact another administrator to modify your role."
            ], 403);
        }

        // Prevent changing Master Super Admin role to something else or assigning Master Super Admin role to others
        if ($targetUserIsMasterSuperAdmin && $request->roles && $request->roles !== 'Master Super Admin' && !$isOwnProfile) {
            return response()->json([
                'status' => 403,
                'message' => "Cannot change Master Super Admin role. This role is protected."
            ], 403);
        }

        if (!$currentUserIsMasterSuperAdmin && $request->roles === 'Master Super Admin') {
            return response()->json([
                'status' => 403,
                'message' => "You do not have permission to assign Master Super Admin role."
            ], 403);
        }

        // Set up validation rules
        $validationRules = [
            'name_title' => 'required|string|max:10',
            'full_name' => 'required|string',
            'user_name' => 'required|string|max:50|unique:users,user_name,' . $user->id,
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:5|confirmed',
            'location_id' => 'required|array',
            'location_id.*' => 'exists:locations,id',
        ];

        // Only require role validation if it's not the user's own profile
        if (!$isOwnProfile) {
            $validationRules['roles'] = 'required|string|exists:roles,name';
        } else {
            // For own profile, role is optional and will be ignored if provided
            $validationRules['roles'] = 'nullable|string|exists:roles,name';
        }

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        }

        $data = [
            'name_title' => $request->name_title,
            'full_name' => $request->full_name,
            'user_name' => $request->user_name,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        }

        $user->update($data);

        // Only update role if it's not the user's own profile and role is provided
        if (!$isOwnProfile && $request->roles) {
            $user->syncRoles([$request->roles]);
        }

        // Validate and sync locations
        $this->validateAndSyncUserLocations($user, $request->location_id ?? []);

        $message = $isOwnProfile ? 
            "Your profile has been updated successfully!" : 
            "User Details Updated Successfully!";

        return response()->json([
            'status' => 200,
            'message' => $message
        ]);
    }

    public function destroy(int $id)
    {
        $currentUser = auth()->user();
        $userToDelete = User::find($id);

        if (!$userToDelete) {
            return response()->json([
                'status' => 404,
                'message' => "No Such User Found!"
            ], 404);
        }

        // Prevent self-deletion
        if ($currentUser->id === $userToDelete->id) {
            return response()->json([
                'status' => 403,
                'message' => "You cannot delete your own account! This would cause system access issues."
            ], 403);
        }

        $currentUserIsMasterSuperAdmin = $currentUser->roles->where('name', 'Master Super Admin')->count() > 0;
        $targetUserIsMasterSuperAdmin = $userToDelete->roles->where('name', 'Master Super Admin')->count() > 0;
        
        // Prevent deletion of Master Super Admin by non-Master Super Admin users
        if ($targetUserIsMasterSuperAdmin && !$currentUserIsMasterSuperAdmin) {
            return response()->json([
                'status' => 403,
                'message' => "You do not have permission to delete Master Super Admin users."
            ], 403);
        }

        // Prevent deletion of the last Master Super Admin
        if ($targetUserIsMasterSuperAdmin) {
            $masterSuperAdminCount = User::whereHas('roles', function($query) {
                $query->where('name', 'Master Super Admin');
            })->count();
            
            if ($masterSuperAdminCount <= 1) {
                return response()->json([
                    'status' => 403,
                    'message' => "Cannot delete the last Master Super Admin user. This would make the system inaccessible."
                ], 403);
            }
        }

        // Additional protection: Master Super Admin cannot delete other Master Super Admin accounts
        if ($currentUserIsMasterSuperAdmin && $targetUserIsMasterSuperAdmin) {
            return response()->json([
                'status' => 403,
                'message' => "Master Super Admin accounts cannot be deleted to maintain system security."
            ], 403);
        }

        // Proceed with deletion
        $userToDelete->roles()->detach();
        $userToDelete->locations()->detach();
        $userToDelete->delete();

        return response()->json([
            'status' => 200,
            'message' => "User Deleted Successfully!"
        ]);
    }

    /**
     * Validate and sync user locations based on current user's permissions
     */
    private function validateAndSyncUserLocations(User $user, array $locationIds)
    {
        $currentUser = auth()->user();
        
        // Master Super Admin can assign any locations
        if ($this->isMasterSuperAdmin($currentUser)) {
            $user->locations()->sync($locationIds);
            return;
        }

        // Users with bypass permission can assign any locations
        if ($this->hasLocationBypassPermission($currentUser)) {
            $user->locations()->sync($locationIds);
            return;
        }

        // Regular users can only assign locations they have access to
        $currentUserLocationIds = $currentUser->locations->pluck('id')->toArray();
        $validLocationIds = array_intersect($locationIds, $currentUserLocationIds);

        if (count($validLocationIds) !== count($locationIds)) {
            $invalidIds = array_diff($locationIds, $currentUserLocationIds);
            throw new \Exception("You cannot assign the following locations as you don't have access to them: " . implode(', ', $invalidIds));
        }

        $user->locations()->sync($validLocationIds);
    }

    /**
     * Check if user is Master Super Admin
     */
    private function isMasterSuperAdmin($user): bool
    {
        if (!$user->relationLoaded('roles')) {
            $user->load('roles');
        }

        return $user->roles->pluck('name')->contains('Master Super Admin') || 
               $user->roles->pluck('key')->contains('master_super_admin');
    }

    /**
     * Check if user has location bypass permission
     */
    private function hasLocationBypassPermission($user): bool
    {
        if (!$user->relationLoaded('roles')) {
            $user->load('roles');
        }

        // Check if any role has bypass_location_scope flag
        foreach ($user->roles as $role) {
            if ($role->bypass_location_scope ?? false) {
                return true;
            }
        }

        // Check for specific permissions
        return $user->hasPermissionTo('override location scope');
    }
}
