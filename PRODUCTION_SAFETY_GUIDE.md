# Production Database Safety Guide for Ledger Cleanup

## ⚠️ CRITICAL SAFETY MEASURES

### 1. BACKUP FIRST (MANDATORY)
```bash
# Create full database backup
mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql

# Or using Laravel's backup package
php artisan backup:run --only-db
```

### 2. TEST ON STAGING ENVIRONMENT
- Clone production database to staging
- Run all cleanup commands on staging first
- Verify results thoroughly before touching production

### 3. MAINTENANCE MODE
```bash
# Put application in maintenance mode
php artisan down --message="System maintenance in progress"

# After completion
php artisan up
```

## 🔍 SAFE EXECUTION STRATEGY

### Phase 1: Analysis Only (READ-ONLY)
```bash
# Check specific customer without making changes
php artisan ledger:recalculate-balance "CUSTOMER_NAME"

# List all customers with mismatches (READ-ONLY)
php artisan ledger:recalculate-balance --list-customers

# Full system analysis (READ-ONLY)
php artisan ledger:recalculate-balance --all
```

### Phase 2: Interactive Mode (CONTROLLED)
```bash
# Process customers one by one with confirmation
php artisan ledger:recalculate-balance --interactive
```

### Phase 3: Targeted Fixes (CAREFUL)
```bash
# Fix only balance calculations (safer)
php artisan ledger:recalculate-balance "CUSTOMER_NAME" --fix-mismatches

# Remove payments only (more risky)
php artisan ledger:recalculate-balance "CUSTOMER_NAME" --remove-payments

# Full cleanup (highest risk)
php artisan ledger:recalculate-balance "CUSTOMER_NAME" --clean-all
```

## 🛡️ PRODUCTION MODIFICATIONS NEEDED

### 1. Add Confirmation Prompts
- Double confirmation for destructive operations
- Show what will be deleted before deletion
- Require explicit approval for each action

### 2. Add Rollback Capability
- Create transaction logs before changes
- Store deleted records in backup tables
- Implement undo functionality

### 3. Add Dry-Run Mode
- Preview changes without executing
- Show impact analysis
- Generate reports of what would change

## 📋 PRE-PRODUCTION CHECKLIST

- [ ] Full database backup created
- [ ] Staging environment tested
- [ ] Application in maintenance mode
- [ ] All users logged out
- [ ] Backup verified and downloadable
- [ ] Rollback plan prepared
- [ ] Team notification sent

## 🚨 EMERGENCY PROCEDURES

### If Something Goes Wrong:
1. Immediately restore from backup
2. Take application offline
3. Investigate the issue
4. Fix the problem
5. Re-test on staging
6. Re-deploy safely

### Rollback Commands:
```bash
# Restore database from backup
mysql -u username -p database_name < backup_file.sql

# Clear application cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

## 📊 RECOMMENDED EXECUTION ORDER

1. **Analysis Phase** (1-2 hours)
   - Run read-only analysis
   - Document all issues found
   - Plan fix strategy

2. **Small Batch Testing** (30 minutes)
   - Fix 2-3 customers only
   - Verify results thoroughly
   - Check application functionality

3. **Full Deployment** (2-4 hours)
   - Process remaining customers
   - Monitor system health
   - Verify business operations

## 🔧 MONITORING DURING EXECUTION

- Monitor database connections
- Check application logs for errors
- Verify critical business functions
- Keep backup accessible
- Have rollback plan ready

Remember: It's better to be slow and safe than fast and sorry!