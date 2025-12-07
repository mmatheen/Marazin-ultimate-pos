# Permission Update Guide

## Overview
This update adds new permissions for **Sale Order** and **Cheque Management** features without affecting existing user roles and permissions.

## New Permissions Added

### POS Management (Group 17)
- `create sale-order` - Create sale orders from POS
- `view sale-order` - View sale order list

### Payment Management (Group 20)
- `manage cheque` - Manage cheque payments
- `view cheque` - View cheque details
- `approve cheque` - Approve cheque payments
- `reject cheque` - Reject cheque payments
- `view cheque-management` - Access cheque management page

## Smart Assignment Logic

The seeder will **automatically** assign new permissions to existing roles based on related permissions:

| New Permission | Assigned If Role Has Any Of These |
|----------------|-----------------------------------|
| `create sale-order` | `save draft` OR `create sale` |
| `view sale-order` | `save draft` OR `view all sales` OR `view own sales` |
| `manage cheque` | `cheque payment` OR `create payment` |
| `view cheque` | `cheque payment` OR `view payments` |
| `approve cheque` | `cheque payment` OR `edit payment` |
| `reject cheque` | `cheque payment` OR `edit payment` |
| `view cheque-management` | `cheque payment` OR `view payments` |

## How to Apply

### Step 1: Backup Database (IMPORTANT!)
```powershell
# Create a backup before running
php artisan backup:run --only-db
```

### Step 2: Run the Seeder
```powershell
php artisan db:seed --class=RolesAndPermissionsSeeder
```

### Step 3: Clear Cache
```powershell
php artisan cache:clear
php artisan config:clear
php artisan permission:cache-reset
```

## What Happens

1. **New permissions are created** in the database
2. **Existing roles are scanned** for related permissions
3. **New permissions are automatically assigned** to roles that have related permissions
4. **No existing permissions are removed** or modified
5. **Master Super Admin** and **Super Admin** roles get all permissions automatically

## Example Scenarios

### Scenario 1: User with "save draft" permission
- ✅ Will get: `create sale-order`, `view sale-order`
- ✅ Can now: Create and view sale orders
- ✅ Still has: All original permissions

### Scenario 2: User with "cheque payment" permission
- ✅ Will get: `manage cheque`, `view cheque`, `approve cheque`, `reject cheque`, `view cheque-management`
- ✅ Can now: Access cheque management page
- ✅ Still has: All original permissions

### Scenario 3: User without related permissions
- ℹ️ Will NOT get new permissions
- ✅ Still has: All original permissions
- ℹ️ Admin can manually assign if needed

## Verification

After running the seeder, verify the changes:

```powershell
# Check if new permissions exist
php artisan tinker
>>> \Spatie\Permission\Models\Permission::where('name', 'like', '%sale-order%')->get();
>>> \Spatie\Permission\Models\Permission::where('name', 'like', '%cheque%')->get();

# Check a specific role's permissions
>>> $role = \Spatie\Permission\Models\Role::findByName('YourRoleName');
>>> $role->permissions->pluck('name');
```

## Manual Assignment (If Needed)

If you need to manually assign permissions to a specific role:

```php
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

$role = Role::findByName('RoleName');

// For Sale Orders
$role->givePermissionTo('create sale-order');
$role->givePermissionTo('view sale-order');

// For Cheque Management
$role->givePermissionTo('view cheque-management');
$role->givePermissionTo('manage cheque');
$role->givePermissionTo('view cheque');
$role->givePermissionTo('approve cheque');
$role->givePermissionTo('reject cheque');
```

## Rollback (If Something Goes Wrong)

If you need to rollback:

1. Restore database from backup
2. Or manually remove new permissions:

```php
use Spatie\Permission\Models\Permission;

$newPermissions = [
    'create sale-order',
    'view sale-order',
    'manage cheque',
    'view cheque',
    'approve cheque',
    'reject cheque',
    'view cheque-management'
];

foreach ($newPermissions as $permName) {
    $perm = Permission::where('name', $permName)->first();
    if ($perm) {
        $perm->delete();
    }
}
```

## Updated Files

1. ✅ `database/seeders/RolesAndPermissionsSeeder.php` - Added new permissions and smart assignment logic
2. ✅ `resources/views/includes/sidebar/sidebar.blade.php` - Updated sidebar permissions
3. ✅ `resources/views/sell/pos.blade.php` - Updated POS button permissions

## Testing Checklist

After update:

- [ ] Backup completed successfully
- [ ] Seeder ran without errors
- [ ] Cache cleared
- [ ] Sale Order menu visible to authorized users
- [ ] Cheque Management menu visible to authorized users
- [ ] POS Sale Order button works for authorized users
- [ ] Existing permissions still work
- [ ] No users lost access to features they had before

## Support

If you encounter any issues:

1. Check Laravel logs: `storage/logs/laravel.log`
2. Verify database connection
3. Ensure all migrations are up to date
4. Check user's role and permissions in database
5. Clear all caches again

## Important Notes

⚠️ **This update is ADDITIVE ONLY**
- No permissions are removed
- No existing role configurations are changed
- Only NEW permissions are added intelligently

✅ **Safe to run multiple times**
- Running the seeder multiple times is safe
- It uses `updateOrCreate` for permissions
- Duplicate prevention is built-in

🔒 **Production Safety**
- Always backup before running
- Test in staging environment first
- Run during low-traffic periods
- Have rollback plan ready
