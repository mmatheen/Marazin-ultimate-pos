# Production Stock Fix Deployment Guide

This guide provides multiple options to fix negative stock calculation issues in your production environment.

## Quick Summary
Your local environment is already fixed. For production, choose one of the methods below:

---

## Method 1: Artisan Command (Recommended)

### Step 1: Upload the Command
Ensure your `app/Console/Commands/ReconcileStock.php` file is deployed to production.

### Step 2: Run the Command
```bash
php artisan stock:reconcile --force
```

**Advantages:**
- Uses Laravel's built-in safety features
- Automatic rollback on errors
- Detailed progress reporting
- Built-in duplicate detection

---

## Method 2: Safe PHP Script

### Step 1: Upload the Script
Upload `safe_production_fix.php` to your Laravel root directory.

### Step 2: Run the Script
```bash
php safe_production_fix.php
```

**Advantages:**
- Standalone script
- Comprehensive validation
- Transaction-based safety
- Works without Artisan

---

## Method 3: Manual SQL (For Advanced Users)

### Step 1: Identify Discrepancies
```sql
SELECT 
    p.id as product_id,
    p.product_name,
    lb.id as location_batch_id,
    lb.qty as actual_qty,
    COALESCE(
        (SELECT SUM(
            CASE 
                WHEN stock_type IN ('opening_stock', 'purchase', 'sales_return_with_bill', 'sales_return_without_bill', 'adjustment') 
                THEN quantity 
                WHEN stock_type IN ('sale', 'purchase_return') 
                THEN -quantity 
                ELSE 0 
            END
        ) FROM stock_histories WHERE loc_batch_id = lb.id), 0
    ) as calculated_qty,
    (lb.qty - COALESCE(
        (SELECT SUM(
            CASE 
                WHEN stock_type IN ('opening_stock', 'purchase', 'sales_return_with_bill', 'sales_return_without_bill', 'adjustment') 
                THEN quantity 
                WHEN stock_type IN ('sale', 'purchase_return') 
                THEN -quantity 
                ELSE 0 
            END
        ) FROM stock_histories WHERE loc_batch_id = lb.id), 0
    )) as needed_adjustment
FROM products p
JOIN batches b ON p.id = b.product_id
JOIN location_batches lb ON b.id = lb.batch_id
WHERE lb.qty > 0
HAVING actual_qty != calculated_qty
ORDER BY p.id;
```

### Step 2: Add Adjustment Entries
For each row from Step 1, run:
```sql
INSERT INTO stock_histories (loc_batch_id, quantity, stock_type, created_at, updated_at)
VALUES ({location_batch_id}, {needed_adjustment}, 'adjustment', NOW(), NOW());
```

### Step 3: Verify Fix
Re-run the query from Step 1 to confirm no discrepancies remain.

---

## Safety Checklist

Before running any fix:

1. ✅ **Backup your database**
2. ✅ **Test on staging environment first**
3. ✅ **Verify table structure includes 'adjustment' enum**
4. ✅ **Check current stock values before fixing**
5. ✅ **Have rollback plan ready**

## Rollback Plan

If something goes wrong, you can remove the adjustment entries:
```sql
DELETE FROM stock_histories 
WHERE stock_type = 'adjustment' 
AND created_at >= 'YYYY-MM-DD HH:MM:SS';
```
(Replace the timestamp with when you started the fix)

---

## Expected Results

After running the fix:
- Product Stock History pages will show correct positive values
- Stock calculations will match actual inventory
- Negative stock warnings will be resolved
- All historical data remains intact

## Support

If you encounter issues:
1. Check the error logs
2. Verify database connectivity
3. Ensure proper Laravel environment setup
4. Contact your development team with error details