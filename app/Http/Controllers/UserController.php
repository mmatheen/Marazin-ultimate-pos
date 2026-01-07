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
        $currentUser = auth()->user();

        // Check role hierarchy for access control
        $isMasterSuperAdmin = $currentUser->roles->where('name', 'Master Super Admin')->count() > 0;
        $isSuperAdmin = $currentUser->roles->where('name', 'Super Admin')->count() > 0;
        $hasBypassRole = $currentUser->roles->where('bypass_location_scope', true)->count() > 0;
        $canBypassLocationScope = $isMasterSuperAdmin || $hasBypassRole;

        // Start with base query
        $query = User::with(['roles', 'locations']);

        if (!$isMasterSuperAdmin) {
            // Non-Master Super Admin users cannot see Master Super Admin users
            $query->whereDoesntHave('roles', function($roleQuery) {
                $roleQuery->where('name', 'Master Super Admin');
            });
        }

        // Apply permission-based filtering
        if (!$isMasterSuperAdmin && !$isSuperAdmin) {
            // Since this method has middleware protection, user already has 'view user' permission
            // Now determine scope of access based on additional permissions and location

            $canEditUsers = $this->userHasPermission($currentUser, 'edit user');
            $canDeleteUsers = $this->userHasPermission($currentUser, 'delete user');
            $canCreateUsers = $this->userHasPermission($currentUser, 'create user');

            // If user only has 'view user' but no edit/delete/create permissions,
            // they might be restricted to own location only
            $hasFullUserManagement = $canEditUsers || $canDeleteUsers || $canCreateUsers;

            if (!$hasFullUserManagement) {
                // Limited access - only show users from same location + self
                if (!$canBypassLocationScope) {
                    $userLocationIds = $currentUser->locations->pluck('id')->toArray();

                    if (!empty($userLocationIds)) {
                        $query->where(function($subQuery) use ($userLocationIds, $currentUser) {
                            $subQuery->whereHas('locations', function($locationQuery) use ($userLocationIds) {
                                $locationQuery->whereIn('locations.id', $userLocationIds);
                            })
                            ->orWhere('id', $currentUser->id);
                        });
                    } else {
                        // No location access - show only self
                        $query->where('id', $currentUser->id);
                    }
                } else {
                    // Can bypass location scope but limited permissions - show all non-master admin users
                    // (already filtered out Master Super Admin users above)
                }
            } else {
                // Has full user management permissions, apply location-based filtering
                if (!$canBypassLocationScope) {
                    $userLocationIds = $currentUser->locations->pluck('id')->toArray();

                    if (!empty($userLocationIds)) {
                        $query->where(function($subQuery) use ($userLocationIds, $currentUser) {
                            $subQuery->whereHas('locations', function($locationQuery) use ($userLocationIds) {
                                $locationQuery->whereIn('locations.id', $userLocationIds);
                            })
                            ->orWhere('id', $currentUser->id);
                        });
                    } else {
                        // No location access - show only self
                        $query->where('id', $currentUser->id);
                    }
                }
            }
        } elseif ($isSuperAdmin && !$canBypassLocationScope) {
            // Super Admin with location restrictions
            $userLocationIds = $currentUser->locations->pluck('id')->toArray();

            if (!empty($userLocationIds)) {
                $query->where(function($subQuery) use ($userLocationIds, $currentUser) {
                    $subQuery->whereHas('locations', function($locationQuery) use ($userLocationIds) {
                        $locationQuery->whereIn('locations.id', $userLocationIds);
                    })
                    ->orWhere('id', $currentUser->id);
                });
            }
        }

        $users = $query->get();

        if ($users->isNotEmpty()) {
            return response()->json([
                'status' => 200,
                'message' => $users->map(function ($user) {
                    $role = $user->roles->first();

                    // Check if user has bypass location scope (Master Super Admin or similar)
                    $isMasterSuperAdmin = $user->roles->where('name', 'Master Super Admin')->count() > 0;
                    $hasBypassRole = $user->roles->where('bypass_location_scope', true)->count() > 0;

                    // If user can bypass location scope and has no locations assigned, show "All Locations"
                    if (($isMasterSuperAdmin || $hasBypassRole) && $user->locations->isEmpty()) {
                        $locations = ['All Locations'];
                    } else {
                        $locations = $user->locations->pluck('name')->toArray();
                    }

                    return [
                        'id' => $user->id,
                        'name_title' => $user->name_title,
                        'full_name' => $user->full_name,
                        'user_name' => $user->user_name,
                        'email' => $user->email,
                        'role' => $role?->name ?? '—',
                        'role_key' => $role?->key ?? '—',
                        'locations' => $locations,
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

        // Check role hierarchy
        $currentUserIsMasterSuperAdmin = $currentUser->roles->where('name', 'Master Super Admin')->count() > 0;
        $currentUserIsSuperAdmin = $currentUser->roles->where('name', 'Super Admin')->count() > 0;
        $targetUserIsMasterSuperAdmin = $user->roles->where('name', 'Master Super Admin')->count() > 0;

        // Check if current user can bypass location scope
        $hasBypassRole = $currentUser->roles->where('bypass_location_scope', true)->count() > 0;
        $canBypassLocationScope = $currentUserIsMasterSuperAdmin || $hasBypassRole;
        $isOwnProfile = $currentUser->id === $user->id;

        // Prevent non-Master Super Admin users from editing Master Super Admin users
        if ($targetUserIsMasterSuperAdmin && !$currentUserIsMasterSuperAdmin) {
            return response()->json([
                'status' => 403,
                'message' => "You do not have permission to edit Master Super Admin users."
            ], 403);
        }

        // Check permission-based access (unless it's own profile)
        if (!$isOwnProfile && !$currentUserIsMasterSuperAdmin && !$currentUserIsSuperAdmin) {
            // Check if current user has edit permission
            $canEditUsers = $this->userHasPermission($currentUser, 'edit user');

            if (!$canEditUsers) {
                return response()->json([
                    'status' => 403,
                    'message' => "You do not have permission to edit other users."
                ], 403);
            }
        }

        // Check location-based access (unless it's own profile)
        if (!$isOwnProfile && !$currentUserIsMasterSuperAdmin && !$canBypassLocationScope) {
            $currentUserLocationIds = $currentUser->locations->pluck('id')->toArray();
            $targetUserLocationIds = $user->locations->pluck('id')->toArray();

            // Check if they share at least one location
            $hasSharedLocation = !empty(array_intersect($currentUserLocationIds, $targetUserLocationIds));

            if (!$hasSharedLocation) {
                return response()->json([
                    'status' => 403,
                    'message' => "You do not have permission to edit users from different locations."
                ], 403);
            }
        }

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

        // Check role hierarchy
        $currentUserIsMasterSuperAdmin = $currentUser->roles->where('name', 'Master Super Admin')->count() > 0;
        $currentUserIsSuperAdmin = $currentUser->roles->where('name', 'Super Admin')->count() > 0;
        $targetUserIsMasterSuperAdmin = $user->roles->where('name', 'Master Super Admin')->count() > 0;
        $isOwnProfile = $currentUser->id === $user->id;

        // Check if current user can bypass location scope
        $hasBypassRole = $currentUser->roles->where('bypass_location_scope', true)->count() > 0;
        $canBypassLocationScope = $currentUserIsMasterSuperAdmin || $hasBypassRole;

        // Prevent non-Master Super Admin users from editing Master Super Admin users
        if ($targetUserIsMasterSuperAdmin && !$currentUserIsMasterSuperAdmin) {
            return response()->json([
                'status' => 403,
                'message' => "You do not have permission to edit Master Super Admin users."
            ], 403);
        }

        // Check location-based access (unless it's own profile)
        if (!$isOwnProfile && !$currentUserIsMasterSuperAdmin && !$canBypassLocationScope) {
            $currentUserLocationIds = $currentUser->locations->pluck('id')->toArray();
            $targetUserLocationIds = $user->locations->pluck('id')->toArray();

            // Check if they share at least one location
            $hasSharedLocation = !empty(array_intersect($currentUserLocationIds, $targetUserLocationIds));

            if (!$hasSharedLocation) {
                return response()->json([
                    'status' => 403,
                    'message' => "You do not have permission to edit users from different locations."
                ], 403);
            }
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

        // Prevent changing Master Super Admin role or assigning it to others
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

        // Check role hierarchy
        $currentUserIsMasterSuperAdmin = $currentUser->roles->where('name', 'Master Super Admin')->count() > 0;
        $targetUserIsMasterSuperAdmin = $userToDelete->roles->where('name', 'Master Super Admin')->count() > 0;

        // Check if current user can bypass location scope
        $hasBypassRole = $currentUser->roles->where('bypass_location_scope', true)->count() > 0;
        $canBypassLocationScope = $currentUserIsMasterSuperAdmin || $hasBypassRole;

        // Prevent deletion of Master Super Admin by non-Master Super Admin users
        if ($targetUserIsMasterSuperAdmin && !$currentUserIsMasterSuperAdmin) {
            return response()->json([
                'status' => 403,
                'message' => "You do not have permission to delete Master Super Admin users."
            ], 403);
        }

        // Check role hierarchy access
        if (!$currentUserIsMasterSuperAdmin) {
            $currentUserRole = $currentUser->roles->first();
            $targetUserRole = $userToDelete->roles->first();

            // Super Admin can delete anyone except Master Super Admin (already checked above)
            $currentUserIsSuperAdmin = $currentUser->roles->where('name', 'Super Admin')->count() > 0;

            if (!$currentUserIsSuperAdmin) {
                if ($currentUserRole && $targetUserRole) {
                    $roleAccessMatrix = [
                        'Admin' => ['Admin', 'Sales Rep', 'Cashier', 'Staff'],
                        'Sales Rep' => ['Sales Rep'],
                        'Cashier' => ['Cashier'],
                        'Staff' => ['Staff'],
                    ];

                    $allowedRoles = $roleAccessMatrix[$currentUserRole->name] ?? [];

                    if (!in_array($targetUserRole->name, $allowedRoles)) {
                        return response()->json([
                            'status' => 403,
                            'message' => "You do not have permission to delete users with {$targetUserRole->name} role."
                        ], 403);
                    }
                }
            }
        }

        // Check location-based access
        if (!$currentUserIsMasterSuperAdmin && !$canBypassLocationScope) {
            $currentUserLocationIds = $currentUser->locations->pluck('id')->toArray();
            $targetUserLocationIds = $userToDelete->locations->pluck('id')->toArray();

            // Check if they share at least one location
            $hasSharedLocation = !empty(array_intersect($currentUserLocationIds, $targetUserLocationIds));

            if (!$hasSharedLocation) {
                return response()->json([
                    'status' => 403,
                    'message' => "You do not have permission to delete users from different locations."
                ], 403);
            }
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
     * Check if user has specific permission (works with Spatie permissions)
     */
    private function userHasPermission($user, $permission): bool
    {
        // Get direct permissions
        $userPermissions = $user->permissions->pluck('name')->toArray();

        // Get role-based permissions
        $rolePermissions = $user->roles->flatMap(function($role) {
            return $role->permissions;
        })->pluck('name')->toArray();

        $allPermissions = array_merge($userPermissions, $rolePermissions);

        return in_array($permission, $allPermissions);
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
