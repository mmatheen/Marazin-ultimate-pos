# Role and Permission Security Implementation

This document outlines the comprehensive security measures implemented to protect the role and permission system in the Marazin Ultimate POS application.

## Security Requirements Addressed

1. **Role Visibility Control**: Non-master-admin users cannot see master admin role details in role tables/lists
2. **Self-Role Protection**: Users cannot edit their own role to prevent access escalation
3. **Master Admin Account Protection**: Master admin accounts cannot be deleted or edited by anyone, including themselves
4. **Permission Scope Control**: Users can only assign permissions they have themselves
5. **Role Hierarchy Enforcement**: Master Super Admin role is fully protected from unauthorized access

## Implementation Details

### 1. Role Visibility Control

**Files Modified:**
- `app/Http/Controllers/RoleAndPermissionController.php`
- `app/Http/Controllers/RoleController.php`
- `app/Http/Controllers/UserController.php`

**Changes:**
- Modified `groupRoleAndPermission()` method to filter roles based on user hierarchy
- Updated `groupRoleAndPermissionList()` to hide Master Super Admin role from non-master users
- Enhanced `index()` methods to return only accessible roles

**Code Example:**
```php
// Get roles that current user can see
if ($isMasterSuperAdmin) {
    $roles = Role::all();
} else {
    $roles = Role::where('name', '!=', 'Master Super Admin')->get();
}
```

### 2. Self-Role Protection

**Security Measure:** Prevents users from editing their own role to avoid access issues and security breaches.

**Implementation:**
- Added checks in `edit()`, `update()`, and `store()` methods
- Middleware-level protection through `RoleSecurityMiddleware`

**Code Example:**
```php
// Prevent users from editing their own role
$userHasThisRole = $currentUser->roles->where('id', $role->id)->count() > 0;
if ($userHasThisRole) {
    return response()->json([
        'status' => 403,
        'message' => 'You cannot edit your own role. This could lead to access issues.'
    ], 403);
}
```

### 3. Master Admin Account Protection

**Security Measures:**
- Master Super Admin users cannot edit their own accounts
- Master Super Admin users cannot be deleted by anyone (including other Master Super Admins)
- Master Super Admin role cannot be changed or deleted
- Only Master Super Admin can perform certain administrative actions

**Files Modified:**
- `app/Http/Controllers/UserController.php`
- `app/Http/Controllers/RoleController.php`
- `app/Http/Controllers/RoleAndPermissionController.php`

**Key Protections:**
```php
// Prevent Master Super Admin from editing their own account
if ($currentUser->id === $user->id && $currentUserIsMasterSuperAdmin) {
    return response()->json([
        'status' => 403,
        'message' => 'Master Super Admin cannot edit their own account to prevent system lockout.'
    ], 403);
}

// Prevent deletion of Master Super Admin accounts
if ($currentUserIsMasterSuperAdmin && $targetUserIsMasterSuperAdmin) {
    return response()->json([
        'status' => 403,
        'message' => 'Master Super Admin accounts cannot be deleted to maintain system security.'
    ], 403);
}
```

### 4. Permission Scope Control

**Security Measure:** Users can only assign permissions that they have themselves, preventing privilege escalation.

**Implementation:**
- Enhanced `store()` and `update()` methods in `RoleAndPermissionController`
- Added validation to check user's actual permissions before allowing assignment

**Code Example:**
```php
// For non-Master Super Admin users, validate they can only assign permissions they have
if (!$isMasterSuperAdmin) {
    $allUserPermissions = $userDirectPermissions->merge($userRolePermissions)->unique('id');
    $userPermissionIds = $allUserPermissions->pluck('id')->toArray();
    
    $invalidPermissions = $selectedPermissions->filter(function($permission) use ($userPermissionIds) {
        return !in_array($permission->id, $userPermissionIds);
    });

    if ($invalidPermissions->count() > 0) {
        return response()->json([
            'status' => 403,
            'message' => 'You can only assign permissions that you have yourself.'
        ], 403);
    }
}
```

### 5. Role Hierarchy Enforcement

**Security Measures:**
- Master Super Admin role is completely protected from modification or deletion
- Only Master Super Admin can manage other roles
- Role creation/modification is strictly controlled

**Code Example:**
```php
// Prevent deletion of Master Super Admin role by anyone, including Master Super Admin
if ($roleToDelete->name === 'Master Super Admin') {
    return response()->json([
        'status' => 403,
        'message' => 'Master Super Admin role cannot be deleted. This role is essential for system operation and security.'
    ], 403);
}
```

## Middleware Implementation

**File:** `app/Http/Middleware/RoleSecurityMiddleware.php`

A comprehensive middleware that provides route-level security for role and user management operations:

- Prevents unauthorized access to Master Super Admin role operations
- Blocks self-role editing attempts
- Protects Master Super Admin user accounts from unauthorized access
- Provides centralized security enforcement

**Registration:** Added to `app/Http/Kernel.php` as `'role.security'`

**Usage:** Applied to relevant controller methods:
```php
$this->middleware('role.security', ['only' => ['edit', 'update', 'store', 'destroy']]);
```

## Security Benefits

1. **Prevents Privilege Escalation**: Users cannot assign themselves higher permissions
2. **Maintains System Integrity**: Master Super Admin role and accounts are fully protected
3. **Prevents Lockouts**: Users cannot accidentally remove their own access
4. **Enforces Proper Hierarchy**: Role-based access control is strictly maintained
5. **Centralized Security**: Middleware provides consistent protection across routes

## Files Modified

### Controllers:
- `app/Http/Controllers/RoleAndPermissionController.php`
- `app/Http/Controllers/RoleController.php`
- `app/Http/Controllers/UserController.php`

### Middleware:
- `app/Http/Middleware/RoleSecurityMiddleware.php` (New)
- `app/Http/Kernel.php` (Updated)

## Testing Recommendations

1. **Test Non-Master Admin Access**: Verify non-master users cannot see or access Master Super Admin roles
2. **Test Self-Role Protection**: Confirm users cannot edit their own roles
3. **Test Master Admin Protection**: Verify Master Super Admin accounts cannot be deleted or modified
4. **Test Permission Scope**: Ensure users can only assign permissions they possess
5. **Test Role Hierarchy**: Confirm role management restrictions are enforced

## Security Notes

- All security checks are implemented at both controller and middleware levels for defense in depth
- Error messages are informative but do not reveal sensitive system information
- The Master Super Admin role is considered the highest privilege level and is fully protected
- All operations maintain audit trails through Laravel's built-in logging mechanisms

## Future Enhancements

1. **Activity Logging**: Implement detailed logging for all role and permission changes
2. **Two-Factor Authentication**: Add 2FA requirement for sensitive operations
3. **Session Validation**: Enhanced session security for administrative operations
4. **IP Restrictions**: Optional IP whitelisting for Master Super Admin access