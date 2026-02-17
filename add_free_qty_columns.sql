-- ═══════════════════════════════════════════════════════════
-- SQL TO ADD FREE QUANTITY COLUMNS (No Migration Needed)
-- ═══════════════════════════════════════════════════════════
-- Run these SQL commands in your database to add free_quantity tracking
-- After running, your calculateFreeQty() methods will work automatically
-- ═══════════════════════════════════════════════════════════

-- Add free_quantity to purchase_products
ALTER TABLE purchase_products
ADD COLUMN free_quantity DECIMAL(15,4) DEFAULT 0 AFTER quantity;

-- Add free_quantity to sales_products
ALTER TABLE sales_products
ADD COLUMN free_quantity DECIMAL(15,4) DEFAULT 0 AFTER quantity;

-- Add free_quantity to purchase_return_products
ALTER TABLE purchase_return_products
ADD COLUMN free_quantity DECIMAL(15,4) DEFAULT 0 AFTER quantity;

-- Add free_quantity to sales_return_products
ALTER TABLE sales_return_products
ADD COLUMN free_quantity DECIMAL(15,4) DEFAULT 0 AFTER quantity;

-- Add free_quantity to adjustment_products
ALTER TABLE adjustment_products
ADD COLUMN free_quantity DECIMAL(15,4) DEFAULT 0 AFTER quantity;

-- ═══════════════════════════════════════════════════════════
-- DONE! Now the calculateFreeQty() methods will work
-- ═══════════════════════════════════════════════════════════
-- Next steps:
-- 1. Run these SQL commands
-- 2. Test with: php test_free_qty.php
-- 3. When recording purchases, set free_quantity field
-- 4. Use $batch->calculateFreeQty() anywhere to get free qty remaining
-- ═══════════════════════════════════════════════════════════
