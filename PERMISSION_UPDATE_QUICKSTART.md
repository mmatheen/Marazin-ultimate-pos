# Quick Start Guide - Permission Update

## What Changed?

✅ **Sale Order** now has its own permission (was using "save draft")  
✅ **Cheque Management** now has its own permissions (was using sales permissions)  
✅ **Smart assignment** automatically gives new permissions to users who have related permissions  
✅ **No breaking changes** - all existing permissions remain intact

## Quick Update (3 Steps)

### Step 1: Backup (Recommended)
```powershell
php artisan backup:run --only-db
```

### Step 2: Run Update Command
```powershell
php artisan permissions:update
```

### Step 3: Verify
```powershell
php artisan permissions:verify
```

## What Will Happen?

The update command will:

1. ✅ Create 7 new permissions
2. ✅ Scan all existing roles
3. ✅ Automatically assign new permissions based on related permissions:
   - If user has **"save draft"** → Gets **"create sale-order"** and **"view sale-order"**
   - If user has **"cheque payment"** → Gets all **cheque management permissions**
4. ✅ Clear all caches
5. ✅ Keep all existing permissions unchanged

## Example: What Users Will Get

### User with "save draft" permission
**Before:**
- ✅ save draft
- ✅ suspend sale
- ✅ access pos

**After:**
- ✅ save draft *(unchanged)*
- ✅ suspend sale *(unchanged)*
- ✅ access pos *(unchanged)*
- ✨ **create sale-order** *(NEW)*
- ✨ **view sale-order** *(NEW)*

### User with "cheque payment" permission
**Before:**
- ✅ cheque payment
- ✅ view payments

**After:**
- ✅ cheque payment *(unchanged)*
- ✅ view payments *(unchanged)*
- ✨ **manage cheque** *(NEW)*
- ✨ **view cheque** *(NEW)*
- ✨ **approve cheque** *(NEW)*
- ✨ **reject cheque** *(NEW)*
- ✨ **view cheque-management** *(NEW)*

## Troubleshooting

### Command not found?
```powershell
# Clear composer autoload
composer dump-autoload
```

### Permissions not showing?
```powershell
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan permission:cache-reset
```

### Need to manually assign?
```powershell
php artisan tinker
```
```php
$role = \Spatie\Permission\Models\Role::findByName('YourRoleName');
$role->givePermissionTo('create sale-order');
$role->givePermissionTo('view sale-order');
```

## Files Changed

1. ✅ `database/seeders/RolesAndPermissionsSeeder.php`
2. ✅ `resources/views/includes/sidebar/sidebar.blade.php`
3. ✅ `resources/views/sell/pos.blade.php`
4. ✅ `app/Console/Commands/UpdatePermissions.php` *(NEW)*
5. ✅ `app/Console/Commands/VerifyPermissions.php` *(NEW)*

## Testing Checklist

- [ ] Run `php artisan permissions:update`
- [ ] Run `php artisan permissions:verify`
- [ ] Login as different users
- [ ] Check Sale Order menu in sidebar
- [ ] Check Cheque Management menu in sidebar
- [ ] Test Sale Order button in POS
- [ ] Verify existing features still work

## Rollback (If Needed)

If something goes wrong:

```powershell
# Restore from backup
# Then clear cache
php artisan cache:clear
php artisan config:clear
php artisan permission:cache-reset
```

## Need Help?

- Check logs: `storage/logs/laravel.log`
- Run verification: `php artisan permissions:verify`
- Read detailed guide: `UPDATE_PERMISSIONS.md`

---

**Status:** Ready to deploy ✅  
**Safe to run:** Multiple times ✅  
**Breaking changes:** None ✅  
**Backup required:** Recommended ✅
