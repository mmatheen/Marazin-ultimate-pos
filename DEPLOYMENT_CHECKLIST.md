# DEPLOYMENT CHECKLIST

## Pre-Deployment

- [ ] Read `README_PERMISSIONS.md`
- [ ] Read `PERMISSION_UPDATE_QUICKSTART.md`
- [ ] Review changes in `IMPLEMENTATION_SUMMARY.md`
- [ ] Understand `PERMISSION_FLOW_DIAGRAM.md`
- [ ] Have database backup strategy ready
- [ ] Have rollback plan documented
- [ ] Inform team about deployment

## Backup Phase

- [ ] Stop any running jobs/queues (if applicable)
- [ ] Create full database backup
  ```powershell
  php artisan backup:run --only-db
  ```
- [ ] Verify backup was created successfully
- [ ] Store backup in safe location
- [ ] Document backup location and timestamp

## Code Deployment

- [ ] Pull latest code from repository
  ```powershell
  git pull origin main
  ```
- [ ] Check all files are present:
  - [ ] `database/seeders/RolesAndPermissionsSeeder.php`
  - [ ] `resources/views/includes/sidebar/sidebar.blade.php`
  - [ ] `resources/views/sell/pos.blade.php`
  - [ ] `app/Console/Commands/UpdatePermissions.php`
  - [ ] `app/Console/Commands/VerifyPermissions.php`

- [ ] Clear composer autoload
  ```powershell
  composer dump-autoload
  ```

## Permission Update

- [ ] Run permission update command
  ```powershell
  php artisan permissions:update
  ```

- [ ] Verify command completed successfully
- [ ] Check for any error messages
- [ ] Review output for:
  - [ ] "7 new permissions created" message
  - [ ] "Assigned permissions to existing roles" messages
  - [ ] "Cache cleared" confirmation

## Verification

- [ ] Run verification command
  ```powershell
  php artisan permissions:verify
  ```

- [ ] Verify all 7 new permissions exist:
  - [ ] `create sale-order`
  - [ ] `view sale-order`
  - [ ] `manage cheque`
  - [ ] `view cheque`
  - [ ] `approve cheque`
  - [ ] `reject cheque`
  - [ ] `view cheque-management`

- [ ] Check role assignment report looks correct
- [ ] Verify expected roles have new permissions

## Cache Clearing

- [ ] Clear application cache
  ```powershell
  php artisan cache:clear
  ```

- [ ] Clear config cache
  ```powershell
  php artisan config:clear
  ```

- [ ] Clear permission cache
  ```powershell
  php artisan permission:cache-reset
  ```

- [ ] Clear view cache (optional)
  ```powershell
  php artisan view:clear
  ```

## Testing Phase

### Test as Super Admin

- [ ] Login as Super Admin user
- [ ] Navigate to sidebar
  - [ ] "Sale Orders" menu is visible
  - [ ] "Cheque Management" menu is visible
- [ ] Navigate to POS page
  - [ ] "Sale Order" button is visible (desktop)
  - [ ] "Sale Order" button is visible (mobile)
  - [ ] Click "Sale Order" button - should work
- [ ] Test existing features:
  - [ ] Draft button works
  - [ ] Suspend button works
  - [ ] Quotation button works
  - [ ] Payment methods work

### Test as Manager/Admin Role

- [ ] Login as Manager/Admin
- [ ] Check sidebar visibility:
  - [ ] Sale Orders menu (should be visible if role has permission)
  - [ ] Cheque Management menu (should be visible if role has permission)
- [ ] Navigate to POS:
  - [ ] Check Sale Order button visibility
  - [ ] Test button functionality if visible
- [ ] Verify existing permissions still work

### Test as Regular User (Sales Rep/Cashier)

- [ ] Login as regular user
- [ ] Check sidebar:
  - [ ] Verify appropriate menu visibility
  - [ ] Confirm unauthorized menus are hidden
- [ ] Navigate to POS:
  - [ ] Check button visibility matches permissions
  - [ ] Verify cannot access unauthorized features
- [ ] Test basic functionality works

## Permission Verification

### Manual Database Check

- [ ] Open database or use Tinker
  ```powershell
  php artisan tinker
  ```

- [ ] Check permissions exist
  ```php
  \Spatie\Permission\Models\Permission::whereIn('name', [
    'create sale-order',
    'view sale-order',
    'manage cheque',
    'view cheque',
    'approve cheque',
    'reject cheque',
    'view cheque-management'
  ])->count(); // Should return 7
  ```

- [ ] Check specific role
  ```php
  $role = \Spatie\Permission\Models\Role::findByName('YourRoleName');
  $role->permissions->pluck('name')->toArray();
  ```

- [ ] Verify role has expected permissions

## Log Monitoring

- [ ] Check Laravel logs
  ```powershell
  Get-Content storage/logs/laravel.log -Tail 50
  ```

- [ ] Look for any errors related to:
  - [ ] Permission errors
  - [ ] Authorization errors
  - [ ] Database errors
  - [ ] Cache errors

- [ ] Check web server logs (if applicable)
- [ ] Monitor for 403 Forbidden errors
- [ ] Check for any unexpected behavior

## Functionality Testing

### Sale Order Feature

- [ ] Create a sale order from POS
- [ ] Verify sale order is created successfully
- [ ] Check sale order appears in list
- [ ] Verify sale order details are correct
- [ ] Test with different user roles

### Cheque Management Feature

- [ ] Access Cheque Management page
- [ ] Verify page loads correctly
- [ ] Test cheque-related operations
- [ ] Check permissions work correctly
- [ ] Test with different user roles

### Existing Features (Regression Testing)

- [ ] Draft functionality works
- [ ] Suspend sale works
- [ ] Quotation creation works
- [ ] Job ticket creation works
- [ ] Cash payment works
- [ ] Card payment works
- [ ] Cheque payment works
- [ ] Credit sale works
- [ ] Multiple payment works
- [ ] Product search works
- [ ] Customer selection works
- [ ] Discount application works

## Edge Cases

- [ ] Test user with NO related permissions
  - [ ] Should NOT see new menus
  - [ ] Should NOT see new buttons
  - [ ] Should get 403 if trying direct access

- [ ] Test user with partial permissions
  - [ ] Verify they only see what they should
  - [ ] Test mixed permission scenarios

- [ ] Test permission changes
  - [ ] Remove permission from user
  - [ ] Verify access is immediately revoked
  - [ ] Add permission back
  - [ ] Verify access is granted

## Performance Check

- [ ] Page load times are normal
- [ ] No significant slowdown
- [ ] Permission checks are fast
- [ ] Database queries are optimized

## Documentation

- [ ] Update internal wiki (if applicable)
- [ ] Notify team of changes
- [ ] Document any issues found
- [ ] Document any manual adjustments made

## Post-Deployment

- [ ] Monitor system for 24 hours
- [ ] Check for user reports of issues
- [ ] Review logs regularly
- [ ] Be ready to rollback if needed

## Rollback Plan (If Needed)

- [ ] Stop all users (maintenance mode)
  ```powershell
  php artisan down
  ```

- [ ] Restore database from backup
- [ ] Clear all caches
  ```powershell
  php artisan cache:clear
  php artisan config:clear
  php artisan permission:cache-reset
  ```

- [ ] Bring system back up
  ```powershell
  php artisan up
  ```

- [ ] Notify team of rollback
- [ ] Document reason for rollback

## Sign-Off

### Deployment Information
- **Deployed By:** ________________
- **Date:** ________________
- **Time:** ________________
- **Environment:** [ ] Production [ ] Staging [ ] Development

### Verification Sign-Off
- [ ] All checks passed
- [ ] No critical issues found
- [ ] Team notified
- [ ] Documentation updated

**Verified By:** ________________  
**Date:** ________________  
**Signature:** ________________

### Issues Found (If Any)
```
List any issues discovered during deployment:

1. 
2. 
3. 

Resolution:

```

### Notes
```
Additional notes or observations:



```

---

## Quick Reference Commands

```powershell
# Full deployment sequence
php artisan backup:run --only-db
composer dump-autoload
php artisan permissions:update
php artisan permissions:verify
php artisan cache:clear
php artisan config:clear
php artisan permission:cache-reset

# Verification
php artisan tinker
>>> \Spatie\Permission\Models\Permission::where('name', 'like', '%sale-order%')->get();

# Rollback
php artisan down
# Restore database
php artisan cache:clear
php artisan config:clear
php artisan permission:cache-reset
php artisan up
```

---

**Checklist Version:** 1.0  
**Last Updated:** December 2024  
**For:** Marazin Ultimate POS - Permission Update
