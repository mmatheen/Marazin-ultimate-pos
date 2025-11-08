# Ledger Missing Entries - Fix Documentation

## Problem Identified

Your sales system has **LocationScope** applied to the Customer model, which can cause issues when:

1. **Sales are created** but the ledger service cannot verify the customer exists (due to location filtering)
2. **Customers belong to different locations** than the current user's access
3. **Missing withoutGlobalScopes()** when querying customers for ledger entries

## Solution Applied

### 1. Created Fix Command
**File**: `app/Console/Commands/FixMissingLedgerEntries.php`

This command will:
- ✅ Scan all sales for missing ledger entries
- ✅ Bypass LocationScope to see ALL sales and customers
- ✅ Create missing ledger entries
- ✅ Recalculate customer balances
- ✅ Provide detailed reports

### 2. Usage

#### Check for Missing Entries (Dry Run)
```bash
php artisan ledger:fix-missing --check
```

#### Fix Missing Entries
```bash
php artisan ledger:fix-missing
```

#### Fix for Specific Customer
```bash
php artisan ledger:fix-missing --customer=5
```

## Root Cause

The **LocationScope** in the Customer model filters customers by location. When ledger entries are created:

```php
// WITHOUT withoutGlobalScopes - customer might be filtered out
$customer = Customer::find($customerId); // ❌ May fail due to LocationScope

// WITH withoutGlobalScopes - gets customer regardless of location
$customer = Customer::withoutGlobalScopes()->find($customerId); // ✅ Always works
```

## Prevention - Future Sales

The sale creation already handles this correctly:

```php
// Line 901 in SaleController.php
$customer = Customer::withoutGlobalScopes()->findOrFail($request->customer_id);
```

And ledger recording:

```php
// Line 1136 in SaleController.php
if ($request->customer_id != 1 && !$isUpdate) {
    $this->unifiedLedgerService->recordSale($sale);
}
```

## Manual Database Check

To check for issues manually:

```sql
-- Find sales without ledger entries
SELECT s.id, s.invoice_no, s.customer_id, c.first_name, c.last_name, s.final_total, s.status
FROM sales s
LEFT JOIN customers c ON s.customer_id = c.id
LEFT JOIN ledgers l ON l.user_id = s.customer_id 
    AND l.contact_type = 'customer' 
    AND l.transaction_type = 'sale'
    AND (l.reference_no = s.invoice_no OR l.reference_no = CONCAT('INV-', s.id))
WHERE s.customer_id IS NOT NULL 
    AND s.customer_id != 1
    AND s.status NOT IN ('draft', 'quotation')
    AND l.id IS NULL;

-- Check ledgers with NULL user_id
SELECT COUNT(*) as missing_user_id_count 
FROM ledgers 
WHERE user_id IS NULL OR user_id = 0;

-- Fix ledgers with NULL user_id if you can identify the customer
-- (You'll need to match by reference_no to sales table)
UPDATE ledgers l
INNER JOIN sales s ON (
    l.reference_no = s.invoice_no OR 
    l.reference_no = CONCAT('INV-', s.id)
)
SET l.user_id = s.customer_id
WHERE l.user_id IS NULL 
    AND l.transaction_type = 'sale'
    AND l.contact_type = 'customer';
```

## Verification After Fix

After running the fix command, verify:

```bash
# Check ledger entries count
php artisan tinker --execute="echo 'Total Ledgers: ' . \App\Models\Ledger::count() . PHP_EOL;"

# Check customer balances
php artisan tinker --execute="\$customers = \App\Models\Customer::withoutGlobalScopes()->where('id', '!=', 1)->take(5)->get(); foreach(\$customers as \$c) { echo 'Customer: ' . \$c->first_name . ' | Balance: ' . \$c->current_balance . PHP_EOL; }"
```

## Important Notes

⚠️ **Always backup your database before running fix commands**

✅ The fix command is **idempotent** - safe to run multiple times

✅ Uses **withoutGlobalScopes()** to bypass location filtering

✅ Maintains **transaction integrity** and chronological order

## Stock Fix Applied Earlier

We also fixed the stock race condition issues in:
- `app/Http/Controllers/SaleController.php`
- `app/Http/Controllers/Api/SaleController.php`
- `app/Http/Controllers/Web/SaleController.php`

These fixes prevent the "Insufficient Stock" errors when stock is actually available.
