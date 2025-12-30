-- Check Stock for Batch 471 (Product 474)
-- Run this query BEFORE and AFTER making a sale to see if stock is deducted

SELECT
    lb.id AS location_batch_id,
    lb.batch_id,
    b.batch_no,
    b.product_id,
    p.product_name,
    p.stock_alert,
    lb.location_id,
    l.name AS location_name,
    lb.qty AS current_stock,
    lb.updated_at AS last_updated
FROM location_batches lb
JOIN batches b ON lb.batch_id = b.id
JOIN products p ON b.product_id = p.id
JOIN locations l ON lb.location_id = l.id
WHERE b.id = 471 OR b.product_id = 474
ORDER BY lb.location_id, lb.batch_id;

-- Check Stock History for Recent Sales
SELECT
    sh.id,
    sh.loc_batch_id,
    lb.batch_id,
    b.batch_no,
    sh.quantity,
    sh.stock_type,
    sh.created_at,
    lb.location_id
FROM stock_histories sh
LEFT JOIN location_batches lb ON sh.loc_batch_id = lb.id
LEFT JOIN batches b ON lb.batch_id = b.id
WHERE (lb.batch_id = 471 OR b.product_id = 474)
ORDER BY sh.created_at DESC
LIMIT 20;

-- Check Recent Sales for Product 474
SELECT
    sp.id,
    sp.sale_id,
    s.invoice_no,
    s.status,
    sp.product_id,
    sp.batch_id,
    sp.location_id,
    sp.quantity,
    sp.created_at
FROM sales_products sp
JOIN sales s ON sp.sale_id = s.id
WHERE sp.product_id = 474
ORDER BY sp.created_at DESC
LIMIT 10;
