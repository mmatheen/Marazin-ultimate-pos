# Production Deployment Guide

## Pre-Deployment Checklist

-   [ ] Test all changes in staging environment
-   [ ] Backup production database
-   [ ] Inform users of maintenance window
-   [ ] Ensure rollback plan is ready

## Step 1: Backup Production Database

```bash
# Create timestamped backup
mysqldump -u [username] -p [database_name] > marazin_pos_backup_$(date +%Y%m%d_%H%M%S).sql

# Or using Laravel backup (if configured)
php artisan backup:run --only-db
```

## Step 2: Deploy Code Changes

```bash
# Pull latest changes
git pull origin main

# Update dependencies
composer install --no-dev --optimize-autoloader

# Update npm packages if needed
npm ci --production
npm run build
```

## Step 3: Run Database Updates (SAFE)

```bash
# Run new migrations only (preserves data)
php artisan migrate

# Update permissions with fixed seeder
php artisan db:seed --class=RolesAndPermissionsSeeder

# Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

## Step 4: Verify Deployment

```bash
# Check application status
php artisan route:list
php artisan config:show database

# Test critical functionality
# - Login with existing users
# - Check permissions are working
# - Verify no duplicate permission errors
```

## Emergency Rollback Plan

If something goes wrong:

```bash
# Restore database from backup
mysql -u [username] -p [database_name] < marazin_pos_backup_[timestamp].sql

# Rollback code changes
git reset --hard [previous_commit_hash]

# Clear caches
php artisan optimize:clear
```

## Important Notes for Your Permission Fix

Your `RolesAndPermissionsSeeder` now safely:

-   ✅ Cleans up duplicate permissions
-   ✅ Merges conflicting permissions
-   ✅ Preserves existing role assignments
-   ✅ Handles foreign key constraints properly

The seeder can be run multiple times safely on production without losing data.

## Production Safety Checklist

-   [ ] ❌ NEVER use `migrate:fresh` on production
-   [ ] ❌ NEVER use `migrate:reset` on production
-   [ ] ✅ ALWAYS backup before changes
-   [ ] ✅ Use `migrate` (not migrate:fresh)
-   [ ] ✅ Test seeders on staging first
-   [ ] ✅ Have rollback plan ready
-   [ ] ✅ Monitor logs after deployment
