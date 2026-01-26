# Sale Order Invoice Fix Script

## Problem Description

This script fixes sale orders that were converted to final sales (credit sales) but didn't get proper invoice numbers due to a bug. 

### Symptoms of Affected Records:
- `transaction_type` = `'sale_order'` 
- `status` = `'final'`
- `invoice_no` = `NULL`
- May have payment records
- No ledger entries created

## What This Script Does

The script will:

1. ✅ **Identify** problematic sale order records
2. ✅ **Generate** proper invoice numbers for each
3. ✅ **Update** `transaction_type` from `'sale_order'` to `'invoice'`
4. ✅ **Update** `order_status` to `'completed'`
5. ✅ **Create** missing ledger entries (for non-Walk-In customers)
6. ✅ **Update** payment reference numbers to use invoice numbers

## Usage

### Step 1: Preview Changes (Dry Run)

**Always run in dry-run mode first to see what will be changed:**

```powershell
php fix_sale_order_invoices.php --dry-run
```

This will show you:
- How many records will be affected
- Details of each record that needs fixing
- What changes will be made
- **No actual changes will be made to the database**

### Step 2: Apply Changes

**After reviewing the dry-run output, apply the fixes:**

```powershell
php fix_sale_order_invoices.php --force
```

This will:
- Make actual changes to the database
- Show progress for each record
- Display a summary at the end

## Output Example

```
=== Sale Order to Invoice Correction Script ===
Started at: 2026-01-26 12:30:45
Mode: DRY RUN (no changes will be made)

Found 3 sale orders that need correction:

Processing Sale ID: 1274
  - Order Number: SO/2026/0001
  - Customer: John Doe (ID: 347)
  - Final Total: Rs 51300.00
  - Payments: 0 payment(s)
  - Created: 2026-01-25 15:30:54
  [DRY RUN] Would generate invoice number
  [DRY RUN] Would update: transaction_type => 'invoice', order_status => 'completed'
  [DRY RUN] Would create ledger entry for customer
  ✅ [DRY RUN] Would fix Sale ID: 1274

=== Summary ===
Total found: 3
Successfully fixed: 3
Failed: 0

Completed at: 2026-01-26 12:30:46
```

## Safety Features

- ✅ **Dry-run mode** - Preview changes without modifying data
- ✅ **Database transactions** - All changes are atomic (all or nothing)
- ✅ **Error handling** - Continues processing if one record fails
- ✅ **Detailed logging** - Shows exactly what's being changed
- ✅ **Duplicate prevention** - Checks for existing ledger entries

## Backup Recommendation

**Before running with --force, backup your database:**

```powershell
# Example backup command (adjust to your setup)
php artisan backup:run
```

Or manually backup these tables:
- `sales`
- `ledgers`
- `payments`

## Troubleshooting

### "No problematic records found"
✅ Good! Your data is clean, no fixes needed.

### "Error fixing Sale ID: XXX"
Check the error message. Common issues:
- Missing customer record
- Location ID issues
- Permission problems

### After Running Script

**Verify the fixes:**

```sql
-- Check that invoice numbers were generated
SELECT id, invoice_no, order_number, transaction_type, status 
FROM sales 
WHERE transaction_type = 'invoice' 
AND invoice_no LIKE 'INV%'
ORDER BY id DESC 
LIMIT 10;

-- Check ledger entries were created
SELECT s.id, s.invoice_no, l.type, l.debit, l.credit 
FROM sales s
LEFT JOIN ledgers l ON l.reference_id = s.id AND l.type = 'sale'
WHERE s.transaction_type = 'invoice'
ORDER BY s.id DESC
LIMIT 10;
```

## Questions?

If you encounter issues:
1. Check the error messages in the script output
2. Review your database backup
3. Check Laravel logs: `storage/logs/laravel.log`

## Related Files Modified

This fix script works alongside these code changes:
- `resources/views/sell/pos_ajax.blade.php` - Frontend fix
- `app/Http/Controllers/SaleController.php` - Backend fix

These prevent the issue from happening again for new records.
