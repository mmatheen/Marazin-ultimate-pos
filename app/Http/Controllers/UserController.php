<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\User\UserAccessService;
use App\Services\User\UserProfileImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    private UserAccessService $userAccessService;
    private UserProfileImageService $profileImageService;

    function __construct()
    {
        $this->userAccessService = app(UserAccessService::class);
        $this->profileImageService = app(UserProfileImageService::class);
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

    public function index()
    {
        $currentUser = auth()->user();
        $currentUserCanViewAllLocations = $this->userAccessService->isMasterSuperAdmin($currentUser)
            || $this->userAccessService->hasLocationBypassPermission($currentUser);
        $currentUserLocationIds = $this->userAccessService->getUserLocationIds($currentUser);

        $users = $this->userAccessService->getVisibleUsersQuery($currentUser)->get();

        if ($users->isNotEmpty()) {
            return response()->json([
                'status' => 200,
                'message' => $users->map(function ($user) use ($currentUserCanViewAllLocations, $currentUserLocationIds) {
                    $role = $user->roles->first();

                    // Check if user has bypass location scope (Master Super Admin or similar)
                    $isMasterSuperAdmin = $this->userAccessService->isMasterSuperAdmin($user);
                    $hasBypassRole = $this->userAccessService->hasLocationBypassPermission($user);

                    $visibleLocations = $user->locations;

                    // Restricted users should only see locations they are allowed to access.
                    if (!$currentUserCanViewAllLocations) {
                        $visibleLocations = $user->locations->whereIn('id', $currentUserLocationIds);
                    }

                    // If user can bypass location scope and has no locations assigned, show "All Locations"
                    if ($currentUserCanViewAllLocations && ($isMasterSuperAdmin || $hasBypassRole) && $visibleLocations->isEmpty()) {
                        $locations = ['All Locations'];
                    } else {
                        $locations = $visibleLocations->pluck('name')->values()->toArray();
                    }

                    return [
                        'id' => $user->id,
                        'name_title' => $user->name_title,
                        'full_name' => $user->full_name,
                        'user_name' => $user->user_name,
                        'email' => $user->email,
                        'profile_image_url' => $this->profileImageService->resolveProfileImageUrl($user->profile_image),
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
        $currentUser = auth()->user();

        $validator = Validator::make(
            $request->all(),
            [
                'name_title' => 'nullable|string|max:10',
                'full_name' => 'required|string',
                'user_name' => 'required|string|max:50|unique:users,user_name',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:5|confirmed',
                'profile_image' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
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

        if (!$this->userAccessService->canAssignRole($currentUser, $request->roles)) {
            return response()->json([
                'status' => 403,
                'message' => 'You do not have permission to assign this role.'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $user = User::create([
                'name_title' => $request->input('name_title') ?: null,
                'full_name' => $request->full_name,
                'user_name' => $request->user_name,
                'email' => $request->email,
                'profile_image' => $this->profileImageService->uploadProfileImage($request),
                'password' => bcrypt($request->password),
            ]);

            $user->assignRole($request->roles);

            // Validate and sync locations
            $this->userAccessService->validateAndSyncUserLocations($currentUser, $user, $request->location_id ?? []);

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => "New User Details Created Successfully!"
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 403,
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    public function show(int $id)
    {
        $currentUser = auth()->user();
        $user = User::with(['roles', 'locations'])->find($id);

        if ($user) {
            $currentUserIsMasterSuperAdmin = $this->userAccessService->isMasterSuperAdmin($currentUser);
            $targetUserIsMasterSuperAdmin = $this->userAccessService->isMasterSuperAdmin($user);
            $targetUserIsSuperAdmin = $this->userAccessService->isSuperAdmin($user) && !$targetUserIsMasterSuperAdmin;
            $canBypassLocationScope = $this->userAccessService->hasLocationBypassPermission($currentUser);
            $isOwnProfile = $currentUser->id === $user->id;

            if ($targetUserIsMasterSuperAdmin && !$currentUserIsMasterSuperAdmin) {
                return response()->json([
                    'status' => 403,
                    'message' => "You do not have permission to view Master Super Admin users."
                ], 403);
            }

            if ($targetUserIsSuperAdmin && !$currentUserIsMasterSuperAdmin) {
                return response()->json([
                    'status' => 403,
                    'message' => "You do not have permission to view Super Admin users."
                ], 403);
            }

            if (!$isOwnProfile && !$currentUserIsMasterSuperAdmin && !$canBypassLocationScope) {
                if (!$this->userAccessService->hasSharedLocationAccess($currentUser, $user)) {
                    return response()->json([
                        'status' => 403,
                        'message' => "You do not have permission to view users from different locations."
                    ], 403);
                }
            }

            $visibleLocations = $this->getVisibleLocationsForViewer($currentUser, $user);

            return response()->json([
                'status' => 200,
                'message' => [
                    'id' => $user->id,
                    'name_title' => $user->name_title,
                    'full_name' => $user->full_name,
                    'user_name' => $user->user_name,
                    'email' => $user->email,
                    'role' => $user->getRoleNames()->first(),
                    'location_ids' => $visibleLocations->pluck('id')->toArray(),
                    'locations' => $visibleLocations->pluck('name')->toArray(),
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
        $currentUserIsMasterSuperAdmin = $this->userAccessService->isMasterSuperAdmin($currentUser);
        $currentUserIsSuperAdmin = $this->userAccessService->isSuperAdmin($currentUser);
        $targetUserIsMasterSuperAdmin = $this->userAccessService->isMasterSuperAdmin($user);
        $targetUserIsSuperAdmin = $this->userAccessService->isSuperAdmin($user) && !$targetUserIsMasterSuperAdmin;

        // Check if current user can bypass location scope
        $canBypassLocationScope = $this->userAccessService->hasLocationBypassPermission($currentUser);
        $isOwnProfile = $currentUser->id === $user->id;

        // Prevent non-Master Super Admin users from editing Master Super Admin users
        if ($targetUserIsMasterSuperAdmin && !$currentUserIsMasterSuperAdmin) {
            return response()->json([
                'status' => 403,
                'message' => "You do not have permission to edit Master Super Admin users."
            ], 403);
        }

        if ($targetUserIsSuperAdmin && !$currentUserIsMasterSuperAdmin) {
            return response()->json([
                'status' => 403,
                'message' => "You do not have permission to edit Super Admin users."
            ], 403);
        }

        // Check permission-based access (unless it's own profile)
        if (!$isOwnProfile && !$currentUserIsMasterSuperAdmin && !$currentUserIsSuperAdmin) {
            // Check if current user has edit permission
            $canEditUsers = $this->userAccessService->userHasPermission($currentUser, 'edit user');

            if (!$canEditUsers) {
                return response()->json([
                    'status' => 403,
                    'message' => "You do not have permission to edit other users."
                ], 403);
            }
        }

        // Check location-based access (unless it's own profile)
        if (!$isOwnProfile && !$currentUserIsMasterSuperAdmin && !$canBypassLocationScope) {
            if (!$this->userAccessService->hasSharedLocationAccess($currentUser, $user)) {
                return response()->json([
                    'status' => 403,
                    'message' => "You do not have permission to edit users from different locations."
                ], 403);
            }
        }

        $visibleLocations = $this->getVisibleLocationsForViewer($currentUser, $user);
        $canEditRole = !$isOwnProfile && $this->userAccessService->getVisibleRolesQuery($currentUser)->exists();

        return response()->json([
            'status' => 200,
            'message' => [
                'id' => $user->id,
                'name_title' => $user->name_title,
                'full_name' => $user->full_name,
                'user_name' => $user->user_name,
                'email' => $user->email,
                'role' => $user->roles->first()?->name ?? 'No Role',
                'location_ids' => $visibleLocations->pluck('id')->toArray(),
                'locations' => $visibleLocations->pluck('name')->toArray(),
                'is_own_profile' => $isOwnProfile,
                'can_edit_role' => $canEditRole
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
        $currentUserIsMasterSuperAdmin = $this->userAccessService->isMasterSuperAdmin($currentUser);
        $currentUserIsSuperAdmin = $this->userAccessService->isSuperAdmin($currentUser);
        $targetUserIsMasterSuperAdmin = $this->userAccessService->isMasterSuperAdmin($user);
        $targetUserIsSuperAdmin = $this->userAccessService->isSuperAdmin($user) && !$targetUserIsMasterSuperAdmin;
        $isOwnProfile = $currentUser->id === $user->id;

        // Check if current user can bypass location scope
        $canBypassLocationScope = $this->userAccessService->hasLocationBypassPermission($currentUser);

        // Prevent non-Master Super Admin users from editing Master Super Admin users
        if ($targetUserIsMasterSuperAdmin && !$currentUserIsMasterSuperAdmin) {
            return response()->json([
                'status' => 403,
                'message' => "You do not have permission to edit Master Super Admin users."
            ], 403);
        }

        if ($targetUserIsSuperAdmin && !$currentUserIsMasterSuperAdmin) {
            return response()->json([
                'status' => 403,
                'message' => "You do not have permission to edit Super Admin users."
            ], 403);
        }

        // Check location-based access (unless it's own profile)
        if (!$isOwnProfile && !$currentUserIsMasterSuperAdmin && !$canBypassLocationScope) {
            if (!$this->userAccessService->hasSharedLocationAccess($currentUser, $user)) {
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
        if ($targetUserIsMasterSuperAdmin && $request->roles && !$this->userAccessService->isMasterSuperAdminRoleName($request->roles) && !$isOwnProfile) {
            return response()->json([
                'status' => 403,
                'message' => "Cannot change Master Super Admin role. This role is protected."
            ], 403);
        }

        if (!$currentUserIsMasterSuperAdmin && $this->userAccessService->isMasterSuperAdminRoleName($request->roles)) {
            return response()->json([
                'status' => 403,
                'message' => "You do not have permission to assign Master Super Admin role."
            ], 403);
        }

        if (!$isOwnProfile && $request->filled('roles') && !$this->userAccessService->canAssignRole($currentUser, $request->roles)) {
            return response()->json([
                'status' => 403,
                'message' => 'You do not have permission to assign this role.'
            ], 403);
        }

        // Set up validation rules
        $validationRules = [
            'name_title' => 'nullable|string|max:10',
            'full_name' => 'required|string',
            'user_name' => 'required|string|max:50|unique:users,user_name,' . $user->id,
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:5|confirmed',
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
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
            'name_title' => $request->input('name_title') ?: null,
            'full_name' => $request->full_name,
            'user_name' => $request->user_name,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        }

        if ($request->hasFile('profile_image')) {
            $data['profile_image'] = $this->profileImageService->uploadProfileImage($request, $user->profile_image);
        }

        try {
            DB::beginTransaction();

            $user->update($data);

            // Only update role if it's not the user's own profile and role is provided
            if (!$isOwnProfile && $request->roles) {
                $user->syncRoles([$request->roles]);
            }

            // Validate and sync locations
            $this->userAccessService->validateAndSyncUserLocations($currentUser, $user, $request->location_id ?? []);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 403,
                'message' => $e->getMessage(),
            ], 403);
        }

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
        $currentUserIsMasterSuperAdmin = $this->userAccessService->isMasterSuperAdmin($currentUser);
        $targetUserIsMasterSuperAdmin = $this->userAccessService->isMasterSuperAdmin($userToDelete);
        $targetUserIsSuperAdmin = $this->userAccessService->isSuperAdmin($userToDelete) && !$targetUserIsMasterSuperAdmin;

        // Check if current user can bypass location scope
        $canBypassLocationScope = $this->userAccessService->hasLocationBypassPermission($currentUser);

        // Prevent deletion of Master Super Admin by non-Master Super Admin users
        if ($targetUserIsMasterSuperAdmin && !$currentUserIsMasterSuperAdmin) {
            return response()->json([
                'status' => 403,
                'message' => "You do not have permission to delete Master Super Admin users."
            ], 403);
        }

        if ($targetUserIsSuperAdmin && !$currentUserIsMasterSuperAdmin) {
            return response()->json([
                'status' => 403,
                'message' => "You do not have permission to delete Super Admin users."
            ], 403);
        }

        // Check role hierarchy access
        if (!$currentUserIsMasterSuperAdmin) {
            $currentUserRole = $currentUser->roles->first();
            $targetUserRole = $userToDelete->roles->first();

            if (!$currentUserRole) {
                return response()->json([
                    'status' => 403,
                    'message' => 'You do not have permission to delete users without an assigned role.'
                ], 403);
            }

            // Super Admin can delete anyone except Master Super Admin (already checked above)
            $currentUserIsSuperAdmin = $this->userAccessService->isSuperAdmin($currentUser);

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
            if (!$this->userAccessService->hasSharedLocationAccess($currentUser, $userToDelete)) {
                return response()->json([
                    'status' => 403,
                    'message' => "You do not have permission to delete users from different locations."
                ], 403);
            }
        }

        // Prevent deletion of the last Master Super Admin
        if ($targetUserIsMasterSuperAdmin) {
            $masterSuperAdminCount = $this->userAccessService->countMasterSuperAdminUsers();

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

    private function getVisibleLocationsForViewer(User $viewer, User $targetUser)
    {
        if ($this->userAccessService->isMasterSuperAdmin($viewer) || $this->userAccessService->hasLocationBypassPermission($viewer)) {
            return $targetUser->locations;
        }

        $viewerLocationIds = $this->userAccessService->getUserLocationIds($viewer);

        return $targetUser->locations->whereIn('id', $viewerLocationIds)->values();
    }

}
