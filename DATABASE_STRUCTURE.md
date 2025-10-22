# 🗄️ DATABASE STRUCTURE & RELATIONSHIPS

## Complete Sale Order System Architecture

```
┌──────────────────────────────────────────────────────────────────┐
│                         USERS TABLE                               │
│  - id                                                             │
│  - name, email, password                                         │
│  - role (admin, sales_rep, etc.)                                 │
└────────────┬─────────────────────────────────────────────────────┘
             │
             │ (user_id)
             ▼
┌──────────────────────────────────────────────────────────────────┐
│                      SALES_REPS TABLE ✅                          │
│  - id                                                             │
│  - user_id (FK → users)                                          │
│  - sub_location_id (FK → locations)                              │
│  - route_id (FK → routes)                                        │
│  - assigned_date                                                 │
│  - can_sell (boolean)                                            │
│  - status (active/inactive)                                      │
└────────────┬─────────────────────────────────────────────────────┘
             │
             │ (sales_rep_id)
             ▼
┌──────────────────────────────────────────────────────────────────┐
│                    SALES TABLE (UNIFIED) ⭐                       │
├──────────────────────────────────────────────────────────────────┤
│  COMMON FIELDS:                                                   │
│  - id                                                             │
│  - customer_id (FK → customers)                                  │
│  - location_id (FK → locations)                                  │
│  - user_id (FK → users) - Who created                            │
│  - sales_rep_id (FK → sales_reps) - Who sold ✨NEW               │
│  - subtotal, discount_amount, final_total                        │
│  - created_at, updated_at                                        │
├──────────────────────────────────────────────────────────────────┤
│  TYPE DISCRIMINATOR:                                              │
│  - transaction_type ENUM('invoice', 'sale_order') ✨NEW          │
├──────────────────────────────────────────────────────────────────┤
│  INVOICE FIELDS (when transaction_type = 'invoice'):             │
│  - invoice_no (unique)                                           │
│  - sales_date                                                    │
│  - payment_status ENUM('Paid', 'Partial', 'Due')                │
│  - total_paid, total_due                                         │
│  - amount_given, balance_amount                                  │
├──────────────────────────────────────────────────────────────────┤
│  SALE ORDER FIELDS (when transaction_type = 'sale_order'):       │
│  - order_number (unique) ✨NEW                                   │
│  - order_date ✨NEW                                              │
│  - expected_delivery_date ✨NEW                                  │
│  - order_status ENUM(...) ✨NEW                                  │
│  - converted_to_sale_id (self-reference) ✨NEW                   │
│  - order_notes ✨NEW                                             │
└────────────┬─────────────────────────────────────────────────────┘
             │
             │ (sale_id)
             ▼
┌──────────────────────────────────────────────────────────────────┐
│                    SALES_PRODUCTS TABLE                           │
│  - id                                                             │
│  - sale_id (FK → sales)                                          │
│  - product_id (FK → products)                                    │
│  - batch_id (FK → batches)                                       │
│  - location_id (FK → locations)                                  │
│  - quantity                                                       │
│  - price, discount_amount, tax                                   │
│  - price_type ENUM('retail', 'wholesale', 'special')            │
└──────────────────────────────────────────────────────────────────┘
```

---

## 🔗 Key Relationships

### 1. Sales Rep → Sales
```php
// One Sales Rep has many Sales/Orders
SalesRep → hasMany → Sale (via sales_rep_id)

// Example:
$salesRep = SalesRep::find(1);
$allOrders = $salesRep->sales; // All sales & orders by this rep
```

### 2. Sale Order → Invoice (Self-Reference)
```php
// One Sale Order converts to one Invoice
Sale (SO) → hasOne → Sale (Invoice) via converted_to_sale_id

// Example:
$saleOrder = Sale::find(1);
$invoice = $saleOrder->convertedSale; // The invoice it became
```

### 3. User → Multiple Roles
```php
User → hasOne → SalesRep (if user is sales rep)
User → hasMany → Sale (as creator via user_id)

// Example:
$user = User::find(1);
$salesRep = $user->salesRep; // If this user is a sales rep
$createdSales = $user->sales; // Sales/Orders created by this user
```

---

## 📊 Data Flow Examples

### Example 1: Complete Sale Order Journey

```
STEP 1: Sales Rep Creates Order
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Sales Table Record #1:
┌────────────────────────────────┐
│ id: 1                          │
│ transaction_type: sale_order   │
│ order_number: SO-2025-0001     │
│ customer_id: 42                │
│ sales_rep_id: 5                │
│ order_date: 2025-10-22         │
│ order_status: pending          │
│ final_total: 5000.00           │
│ invoice_no: NULL               │
│ payment_status: Due            │
│ converted_to_sale_id: NULL     │
└────────────────────────────────┘

Sales_Products Records:
┌────────────────────────────────┐
│ sale_id: 1, product_id: 10     │
│ quantity: 5, price: 800        │
├────────────────────────────────┤
│ sale_id: 1, product_id: 15     │
│ quantity: 2, price: 600        │
└────────────────────────────────┘


STEP 2: Manager Confirms
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
UPDATE sales SET order_status = 'confirmed' WHERE id = 1


STEP 3: Convert to Invoice
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
$saleOrder->convertToInvoice();

Creates NEW Record #2:
┌────────────────────────────────┐
│ id: 2                          │
│ transaction_type: invoice      │
│ invoice_no: INV-2025-0042      │
│ sales_date: 2025-10-23         │
│ customer_id: 42 (same)         │
│ sales_rep_id: 5 (same)         │
│ final_total: 5000.00           │
│ payment_status: Due            │
│ order_number: NULL             │
└────────────────────────────────┘

Updates Original Record #1:
┌────────────────────────────────┐
│ id: 1                          │
│ order_status: completed ✅     │
│ converted_to_sale_id: 2 ✅    │
└────────────────────────────────┘

Copies Items to New Sale:
┌────────────────────────────────┐
│ sale_id: 2, product_id: 10     │
│ quantity: 5, price: 800        │
├────────────────────────────────┤
│ sale_id: 2, product_id: 15     │
│ quantity: 2, price: 600        │
└────────────────────────────────┘


STEP 4: Payment Collection
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Payments Table:
┌────────────────────────────────┐
│ reference_id: 2 (invoice id)   │
│ payment_type: sale             │
│ amount: 5000.00                │
└────────────────────────────────┘

Updates Invoice #2:
┌────────────────────────────────┐
│ total_paid: 5000.00            │
│ total_due: 0.00                │
│ payment_status: Paid ✅        │
└────────────────────────────────┘
```

---

## 🔍 Query Examples

### Get All Sale Orders
```sql
SELECT * FROM sales 
WHERE transaction_type = 'sale_order'
ORDER BY order_date DESC;
```

### Get Pending Orders for a Sales Rep
```sql
SELECT s.*, c.first_name, sr.user_id 
FROM sales s
JOIN customers c ON s.customer_id = c.id
JOIN sales_reps sr ON s.sales_rep_id = sr.id
WHERE s.transaction_type = 'sale_order'
  AND s.order_status IN ('pending', 'confirmed')
  AND s.sales_rep_id = 5
ORDER BY s.order_date DESC;
```

### Get Invoices with Original Sale Order Info
```sql
SELECT 
    inv.id AS invoice_id,
    inv.invoice_no,
    inv.sales_date,
    inv.final_total,
    so.order_number AS original_order_no,
    so.order_date,
    sr.user_id AS sales_rep_user_id
FROM sales inv
LEFT JOIN sales so ON inv.id = so.converted_to_sale_id
LEFT JOIN sales_reps sr ON inv.sales_rep_id = sr.id
WHERE inv.transaction_type = 'invoice'
ORDER BY inv.sales_date DESC;
```

### Sales Rep Performance Report
```sql
-- Orders taken by each sales rep
SELECT 
    sr.id,
    u.name AS sales_rep_name,
    COUNT(CASE WHEN s.transaction_type = 'sale_order' THEN 1 END) AS total_orders,
    COUNT(CASE WHEN s.order_status = 'completed' THEN 1 END) AS completed_orders,
    SUM(CASE WHEN s.transaction_type = 'sale_order' THEN s.final_total END) AS order_value,
    SUM(CASE WHEN s.transaction_type = 'invoice' THEN s.final_total END) AS invoice_value
FROM sales_reps sr
JOIN users u ON sr.user_id = u.id
LEFT JOIN sales s ON sr.id = s.sales_rep_id
WHERE sr.status = 'active'
GROUP BY sr.id, u.name
ORDER BY invoice_value DESC;
```

---

## 🎯 Index Recommendations

Already created in migration:
```sql
-- For fast sale order filtering
CREATE INDEX idx_transaction_type ON sales(transaction_type);
CREATE INDEX idx_order_status ON sales(order_status);
CREATE INDEX idx_order_date ON sales(order_date);

-- For sales rep reports
CREATE INDEX idx_sales_rep_order_date ON sales(sales_rep_id, order_date);

-- For conversion tracking
CREATE INDEX idx_converted_to_sale_id ON sales(converted_to_sale_id);
```

---

## 🔐 Foreign Key Constraints

```sql
-- Sales table relationships
sales.customer_id → customers.id (CASCADE)
sales.location_id → locations.id (CASCADE)
sales.user_id → users.id (CASCADE)
sales.sales_rep_id → sales_reps.id (SET NULL) ✨NEW
sales.converted_to_sale_id → sales.id (SET NULL) ✨NEW

-- Sales_reps relationships
sales_reps.user_id → users.id (CASCADE)
sales_reps.sub_location_id → locations.id (CASCADE)
sales_reps.route_id → routes.id (CASCADE)

-- Sales_products relationships
sales_products.sale_id → sales.id (CASCADE)
sales_products.product_id → products.id (CASCADE)
sales_products.batch_id → batches.id (CASCADE)
sales_products.location_id → locations.id (CASCADE)
```

---

## 📈 Storage Estimates

Assuming average order:
- 1 Sale Order = ~200 bytes base + items
- 5 items = ~500 bytes
- Total per order = ~700 bytes

With 1000 orders/month:
- Sale Orders: 700 KB
- Converted Invoices: 700 KB (separate records)
- Total: ~1.4 MB/month
- Annual: ~17 MB

Very efficient! 🚀

---

**Diagram Created:** October 22, 2025  
**Database Type:** MySQL/MariaDB  
**System:** Marazin Ultimate POS
