-- ================================================================
-- Fix Sales Date Timezone - Convert from UTC to Asia/Colombo
-- ================================================================
-- This script updates all sales_date records in the sales table
-- by converting created_at (UTC) to Asia/Colombo timezone (+5:30)
-- ================================================================

-- Step 1: Backup current sales_date (IMPORTANT - Run this first!)
-- Create a backup table just in case
CREATE TABLE IF NOT EXISTS sales_date_backup AS
SELECT id, sales_date, created_at FROM sales;

-- Step 2: Update sales_date for all records
-- Convert created_at from UTC to Asia/Colombo timezone (+5:30)
UPDATE sales
SET sales_date = CONVERT_TZ(created_at, '+00:00', '+05:30')
WHERE created_at IS NOT NULL;

-- Step 3: Verify the changes
-- Check sample of updated records
SELECT
    id,
    invoice_no,
    created_at AS 'Created At (UTC)',
    sales_date AS 'Sales Date (Asia/Colombo)',
    TIMEDIFF(sales_date, created_at) AS 'Time Difference'
FROM sales
ORDER BY id DESC
LIMIT 20;

-- Step 4: Count affected records
SELECT
    COUNT(*) AS 'Total Records Updated',
    MIN(sales_date) AS 'Earliest Sale',
    MAX(sales_date) AS 'Latest Sale'
FROM sales;

-- ================================================================
-- OPTIONAL: If you want to only update records with wrong timezone
-- (where sales_date doesn't match created_at + 5:30 hours)
-- ================================================================
-- UPDATE sales
-- SET sales_date = CONVERT_TZ(created_at, '+00:00', '+05:30')
-- WHERE created_at IS NOT NULL
-- AND ABS(TIMESTAMPDIFF(MINUTE, sales_date, CONVERT_TZ(created_at, '+00:00', '+05:30'))) > 5;

-- ================================================================
-- To restore from backup (if something goes wrong):
-- ================================================================
-- UPDATE sales s
-- INNER JOIN sales_date_backup b ON s.id = b.id
-- SET s.sales_date = b.sales_date;
--
-- DROP TABLE sales_date_backup;
