# 🚀 PRODUCTION DEPLOYMENT GUIDE

## CRITICAL: Read This Entire Guide Before Running Commands

### 🛡️ Pre-Deployment Checklist

#### 1. Environment Verification
```bash
# Verify you're in the correct environment
php artisan env
php artisan config:show app.env
```

#### 2. Create Complete Backup
```bash
# Database backup
mysqldump -u [username] -p [database_name] > backup_$(date +%Y%m%d_%H%M%S).sql

# File backup (optional)
tar -czf files_backup_$(date +%Y%m%d_%H%M%S).tar.gz storage/ public/storage/
```

#### 3. Test Environment Setup
- Clone production database to staging
- Test all commands on staging first
- Verify results before production run

### 📋 SAFE EXECUTION PHASES

#### Phase 1: READ-ONLY ANALYSIS
```bash
# Step 1: Check specific customer (DUBAIWORLD example)
php artisan ledger:recalculate-balance "DUBAIWORLD" --dry-run

# Step 2: System-wide analysis
php artisan ledger:recalculate-balance --all --dry-run

# Step 3: List problematic customers
php artisan ledger:recalculate-balance --list-customers
```

#### Phase 2: CONTROLLED FIXES
```bash
# Enable maintenance mode
php artisan down --message="System maintenance - Ledger cleanup in progress"

# Interactive mode (SAFEST - processes one customer at a time)
php artisan ledger:recalculate-balance --interactive --backup

# OR specific customer cleanup
php artisan ledger:recalculate-balance "DUBAIWORLD" --clean-all --backup

# Disable maintenance mode
php artisan up
```

#### Phase 3: VERIFICATION
```bash
# Verify specific customer
php artisan ledger:recalculate-balance "DUBAIWORLD"

# System-wide verification
php artisan ledger:recalculate-balance --all
```

### 🔧 COMMAND REFERENCE

#### Analysis Commands (SAFE - READ-ONLY)
```bash
php artisan ledger:recalculate-balance --all --dry-run
php artisan ledger:recalculate-balance "CUSTOMER_NAME" --dry-run  
php artisan ledger:recalculate-balance --list-customers
```

#### Cleanup Commands (DESTRUCTIVE - USE WITH CAUTION)
```bash
# Interactive mode (RECOMMENDED for production)
php artisan ledger:recalculate-balance --interactive --backup

# Specific customer - balance fix only
php artisan ledger:recalculate-balance "CUSTOMER_NAME" --fix-mismatches

# Specific customer - remove payments only
php artisan ledger:recalculate-balance "CUSTOMER_NAME" --remove-payments --backup

# Specific customer - full cleanup (DANGEROUS)
php artisan ledger:recalculate-balance "CUSTOMER_NAME" --clean-all --backup --force
```

### 🚨 EMERGENCY PROCEDURES

#### If Something Goes Wrong:
1. **Immediately restore from backup:**
   ```bash
   mysql -u [username] -p [database_name] < backup_file.sql
   ```

2. **Put application offline:**
   ```bash
   php artisan down --message="Emergency maintenance"
   ```

3. **Clear all caches:**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   ```

4. **Check application logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

#### Recovery Checklist:
- [ ] Database restored
- [ ] Application functionality verified
- [ ] User access tested
- [ ] Critical business operations confirmed

### 💡 PRODUCTION BEST PRACTICES

#### 1. Timing
- Run during low-traffic hours
- Notify users in advance
- Have support team available

#### 2. Monitoring
- Monitor database connections
- Watch application logs
- Check system resources
- Verify business functionality

#### 3. Rollback Plan
- Keep backup file accessible
- Have restore commands ready
- Test rollback procedure beforehand

#### 4. Communication
- Notify stakeholders before starting
- Provide progress updates
- Confirm completion

### 📊 EXPECTED RESULTS

#### For DUBAIWORLD Customer:
- **Before:** Multiple ledger entries, incorrect payments/returns
- **After:** 1 sale entry, balance 20,680, no payments, no returns

#### System-wide:
- Consistent ledger entries
- Accurate customer balances
- No duplicate transactions
- Clean payment records

### 🔍 VALIDATION STEPS

#### After Each Customer Cleanup:
1. Check ledger entry count
2. Verify balance calculation
3. Confirm transaction integrity
4. Test application functionality

#### System Health Checks:
```bash
# Database connectivity
php artisan tinker --execute="DB::connection()->getPdo();"

# Application status
curl -I http://your-domain.com

# Queue status (if applicable)
php artisan queue:work --once
```

### ⚠️ CRITICAL WARNINGS

1. **NEVER run without backup** - Always create backup first
2. **Test on staging** - Never run untested commands on production
3. **Use dry-run** - Always preview changes first
4. **Monitor actively** - Watch logs and system during execution
5. **Have rollback ready** - Be prepared to restore immediately

### 📞 SUPPORT CONTACT

If you encounter issues:
1. Stop the operation immediately
2. Restore from backup if needed
3. Document the error
4. Contact technical support

---

**Remember: Production safety is more important than speed. Take your time and follow the process.**