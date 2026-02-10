# Ledger Fix Script - User Guide

## Overview
This script detects and fixes missing active ledger entries for sales that have been edited or reversed.

## Files Created
1. `fix_missing_ledger_entries.php` - Main fix script
2. `check_mlx_230_ledger.php` - Specific check for MLX-230 (can be deleted)
3. `check_ledger_order.php` - Order verification script (can be deleted)
4. `LEDGER_ORDER_VERIFICATION.md` - Documentation (can be deleted)

## Usage

### 1. Dry Run (Safe - No Changes)
```bash
php fix_missing_ledger_entries.php --dry-run
```
This will show what needs to be fixed WITHOUT making any changes.

### 2. Fix Specific Customer
```bash
php fix_missing_ledger_entries.php --dry-run --customer-id=582
```

### 3. Run the Actual Fix
```bash
php fix_missing_ledger_entries.php
```
This will prompt for confirmation before creating any entries.

### 4. Fix Specific Customer (Live)
```bash
php fix_missing_ledger_entries.php --customer-id=582
```

## What It Does

1. **Scans Database**: Finds sales with:
   - Reversed ledger entries (old entries marked as reversed)
   - NO active ledger entry (the new entry is missing)
   - Not Walk-In customers
   - Finalized sales only

2. **Creates Missing Entries**: 
   - Uses correct `transaction_date` (original sale date)
   - Marks as `status = 'active'`
   - Adds audit note with fix timestamp

3. **Verifies Balances**: 
   - Recalculates customer balances
   - Shows before/after comparison

4. **Creates Audit Log**: 
   - Saves to `storage/logs/ledger_fix_YYYY-MM-DD_HHMMSS.json`

## Safety Features

✅ **Idempotent**: Can be run multiple times safely - won't create duplicates
✅ **Transaction Protected**: Uses DB transactions with rollback on error
✅ **Race Condition Protected**: Double-checks before creating entries
✅ **Dry Run Mode**: Test before making changes
✅ **Confirmation Required**: Asks "yes/no" before proceeding
✅ **Detailed Logging**: All actions are logged
✅ **Audit Trail**: Creates JSON log file

## Expected Output

```
========================================================================
LEDGER FIX SCRIPT - FIND AND FIX MISSING ACTIVE LEDGER ENTRIES
========================================================================
Mode: DRY RUN (no changes will be made)
Started: 2026-02-10 08:30:00
========================================================================

Step 1: Scanning for sales with missing active ledger entries...

Found 1 sales with missing active ledger entries:

─────────────────────────────────────────────────────────────────
Sale ID: 636
Invoice: MLX-230
Customer ID: 582
Amount: Rs. 86025.00
Sale Date: 2025-12-24 16:47:26
Status: final
Reversed Entries: 2
Active Entries: 0 ❌
─────────────────────────────────────────────────────────────────
Total Missing Amount: Rs. 86,025.00
Affected Customers: 1

========================================================================
DRY RUN MODE - No changes made
Run without --dry-run to create the missing ledger entries
========================================================================
```

## When to Use This Script

Use this script if you notice:
- Customer balances are incorrect
- Sales show in reports but not in ledger
- Customer complains about wrong outstanding balance
- After system recovery or data migration
- After fixing sale edit bugs

## Cleanup After Running

Once you've verified everything is working, you can delete these temporary files:
```bash
rm check_mlx_230_ledger.php
rm check_ledger_order.php
rm fix_mlx_230_ledger.php
rm LEDGER_ORDER_VERIFICATION.md
```

Keep `fix_missing_ledger_entries.php` for future use if needed.

## Audit Logs

Audit logs are saved in: `storage/logs/ledger_fix_*.json`

Example log:
```json
{
    "script": "fix_missing_ledger_entries.php",
    "executed_at": "2026-02-10 08:30:00",
    "mode": "live",
    "entries_created": 1,
    "errors": 0,
    "total_amount": 86025.0,
    "affected_customers": 1
}
```

## Troubleshooting

**Q: Script shows 0 issues but balance is still wrong**
A: Run the customer balance debug:
```bash
php artisan tinker
>>> App\Helpers\BalanceHelper::debugCustomerBalance(582);
```

**Q: Script fails with permission error**
A: Ensure `storage/logs/` directory exists and is writable

**Q: How do I verify the fix?**
A:
```bash
# Check specific sale
php artisan tinker
>>> DB::table('ledgers')->where('reference_no', 'MLX-230')->where('status', 'active')->count();
# Should return 1

# Check customer balance
>>> App\Models\Customer::find(582)->calculateBalanceFromLedger();
```

## Support

If you encounter issues:
1. Run with `--dry-run` first
2. Check `storage/logs/laravel.log` for errors
3. Verify database connection
4. Ensure UnifiedLedgerService is working
