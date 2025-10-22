# PURCHASE TOTALS FIX - DEPLOYMENT GUIDE

## What This Fixes:

1. ✅ Purchase `total` field (sum of products before discount/tax)
2. ✅ Purchase `final_total` field (after discount/tax)  
3. ✅ Product-level discount tracking (`price`, `discount_percent`)
4. ✅ Server-side recalculation to prevent client manipulation

---

## Files Changed:

### 1. Database Migration
- `database/migrations/2025_10_22_000001_add_discount_fields_to_purchase_products_table.php`

### 2. Backend Files
- `app/Console/Commands/ReconcileAllPurchases.php` (NEW)
- `app/Http/Controllers/PurchaseController.php` (UPDATED)

### 3. Frontend Files
- `resources/views/purchase/purchase_ajax.blade.php` (UPDATED)

---

## Deployment Steps for Hosting:

### Step 1: Upload Files
Upload all changed files to your hosting server via FTP/SSH.

### Step 2: Run Migration
```bash
php artisan migrate
```

This will add the `price` and `discount_percent` columns to `purchase_products` table.

### Step 3: Check Existing Purchases (DRY RUN)
```bash
php artisan purchase:reconcile-all
```

This shows which purchases need fixing WITHOUT making any changes.

### Step 4: Fix All Purchases
```bash
php artisan purchase:reconcile-all --fix
```

This will actually update the database with correct totals.

### Step 5: Verify
Check a few purchases in your system to verify totals are correct.

---

## What Each Command Does:

### `php artisan purchase:reconcile-all`
- **DRY RUN mode** (default)
- Checks all purchases
- Shows which ones need fixing
- **Does NOT make any changes**
- Safe to run anytime

### `php artisan purchase:reconcile-all --fix`
- **LIVE UPDATE mode**
- Actually fixes the purchase totals
- Updates database
- Shows progress bar
- **Use this after verifying dry-run results**

---

## Expected Results:

### Before Fix:
```
Purchase #1:
  total: 1,876,963.20 (WRONG)
  final_total: 2,501,040.00 (WRONG)
```

### After Fix:
```
Purchase #1:
  total: 2,501,040.00 ✓
  final_total: 2,501,040.00 ✓
```

---

## How It Calculates Totals:

### 1. Product Level:
```
For each product:
  If has discount:
    unit_cost = price - (price × discount_percent / 100)
  Else:
    unit_cost = existing unit_cost
  
  product_total = unit_cost × quantity
```

### 2. Purchase Level:
```
total = SUM(all product_total)

If discount_type = 'fixed':
  discount_amount = discount_amount
Else if discount_type = 'percent':
  discount_amount = total × discount_amount / 100

If tax_type = 'vat10' or 'cgst10':
  tax_amount = (total - discount_amount) × 0.10

final_total = total - discount_amount + tax_amount
```

---

## Troubleshooting:

### Issue: Migration fails with "Column already exists"
**Solution:** The column was already added. Skip migration and go to Step 3.

### Issue: Command not found
**Solution:** Clear cache first:
```bash
php artisan cache:clear
php artisan config:clear
```

### Issue: Still showing wrong totals
**Solution:** 
1. Check if migration ran successfully: `php artisan migrate:status`
2. Re-run the fix command: `php artisan purchase:reconcile-all --fix`
3. Clear browser cache and refresh page

---

## For Future Purchases:

All new purchases will now:
- ✅ Save product-level discount information
- ✅ Calculate totals server-side (can't be manipulated by client)
- ✅ Log any mismatches between client and server calculations
- ✅ Automatically fix incorrect totals

---

## Monitoring:

Check Laravel log file for any warnings:
```
storage/logs/laravel.log
```

Look for entries like:
```
[WARNING] Final total mismatch on purchase store/update
```

This indicates a client sent wrong values but server corrected them.

---

## Rollback (if needed):

If something goes wrong, you can rollback the migration:

```bash
php artisan migrate:rollback --step=1
```

This will remove the `price` and `discount_percent` columns.

---

## Support:

If you encounter any issues:
1. Check `storage/logs/laravel.log` for errors
2. Run dry-run first before applying fixes
3. Test on a small purchase before applying to all
4. Keep a database backup before major updates

---

## Quick Commands Reference:

```bash
# Upload files
# (via FTP or git pull)

# Run migration
php artisan migrate

# Check what needs fixing (safe)
php artisan purchase:reconcile-all

# Actually fix the purchases
php artisan purchase:reconcile-all --fix

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Check migration status
php artisan migrate:status
```
