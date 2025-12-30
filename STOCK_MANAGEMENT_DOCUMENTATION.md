# STOCK MANAGEMENT STRUCTURE - CRITICAL DOCUMENTATION

## ✅ CORRECT TABLES FOR STOCK INVENTORY

### 1. **location_batches** (PRIMARY STOCK TABLE)
```
- id: primary key
- batch_id: FK to batches table
- location_id: FK to locations table  
- qty: decimal(15,4) ★ THE ACTUAL STOCK QUANTITY ★
```

**This is the ONLY authoritative source for stock quantities!**

All stock calculations MUST use:
```sql
SELECT SUM(lb.qty)
FROM location_batches lb
INNER JOIN batches b ON lb.batch_id = b.id
WHERE b.product_id = ? AND lb.location_id = ?
```

### 2. **batches**
```
- id: primary key
- product_id: FK to products
- batch_no: unique batch number
- unit_cost, retail_price, wholesale_price, etc.
- expiry_date
```

Purpose: Groups stock by purchase/production batch with pricing info.

### 3. **products**
```
- id: primary key
- product_name, sku, stock_alert, alert_quantity, etc.
```

Purpose: Product master data.

---

## ❌ DEPRECATED TABLE (DO NOT USE FOR STOCK)

### **location_product** (Pivot Table - ASSIGNMENT ONLY)
```
- id: primary key
- product_id: FK to products
- location_id: FK to locations
- qty: int ❌ DEPRECATED - DO NOT USE!
```

**WARNING**: The `qty` field in this table is:
- ❌ NOT real-time (only synced during product edit, not during sales)
- ❌ NOT reliable for stock calculations
- ❌ Should be IGNORED completely

**Correct Usage**: Only use this table to check IF a product is assigned to a location:
```php
$isAssigned = $product->locations()->where('id', $locationId)->exists();
```

**Never use**: `$product->locations->first()->pivot->qty` ❌

---

## 🔍 CURRENT STATUS (Product 474 Example)

### What the analysis showed:
```
location_product.qty = 10   ❌ WRONG (old cached value)
location_batches.qty = 2    ✅ CORRECT (real stock after sales)
```

### Why the discrepancy?
1. `location_product.qty` is only updated in `ProductController::storeOrUpdate()`
2. Sales/purchases/transfers update `location_batches.qty` but NOT `location_product.qty`  
3. Result: `location_product.qty` becomes stale immediately after first sale

---

## ✅ CORRECT CODE PATTERNS

### Stock Calculation (getAllProductStocks)
```php
// ✅ CORRECT - Line 1938
$totalStock = DB::table('location_batches')
    ->join('batches', 'location_batches.batch_id', '=', 'batches.id')
    ->where('batches.product_id', $product->id)
    ->when($locationId, fn($q) => $q->where('location_batches.location_id', $locationId))
    ->sum('location_batches.qty');
```

### Stock Deduction (SaleController)
```php
// ✅ CORRECT - Deducts from location_batches
$locationBatch = LocationBatch::where('batch_id', $batchId)
    ->where('location_id', $locationId)
    ->first();
$locationBatch->qty -= $quantityToDeduct;
$locationBatch->save();
```

### Product Display (autocompleteStock)
```php
// ✅ CORRECT - Uses location_batches sum
'total_stock' => DB::table('location_batches')
    ->join('batches', 'location_batches.batch_id', '=', 'batches.id')
    ->where('batches.product_id', $product->id)
    ->when($locationId, fn($q) => $q->where('location_batches.location_id', $locationId))
    ->sum('location_batches.qty')
```

---

## ❌ INCORRECT CODE PATTERNS TO AVOID

```php
// ❌ WRONG - Using location_product pivot qty
$stock = $product->locations->where('id', $locationId)->first()->pivot->qty;

// ❌ WRONG - Using eager-loaded collection sum (stale data)
$stock = $batch->locationBatches->sum('qty');

// ✅ CORRECT - Always use fresh DB query
$stock = DB::table('location_batches')
    ->where('batch_id', $batch->id)
    ->sum('qty');
```

---

## 📊 RELATIONSHIP DIAGRAM

```
Product (id, product_name, stock_alert)
   |
   ├──> batches (1:many)
   |       |
   |       └──> location_batches (1:many) ★ STOCK QTY HERE ★
   |               (batch_id, location_id, qty)
   |
   └──> locations (many:many via location_product)
           Pivot: (product_id, location_id, qty ❌ IGNORE)
```

---

## 🔧 RECOMMENDATIONS

1. **Option A (Preferred)**: Remove `qty` column from `location_product` table
   - Drop column via migration
   - Remove `withPivot('qty')` from Product model
   - Remove all `updateExistingPivot()` calls

2. **Option B**: Keep syncing but document clearly
   - Add comment: "// Sync for reference only - DO NOT USE for calculations"
   - Ensure all stock queries use `location_batches`

3. **Option C**: Sync on every transaction
   - Update `location_product.qty` after every sale/purchase/transfer
   - Performance overhead, not recommended

**Current Implementation**: Using Option B (sync exists but documented)

---

## ✅ VERIFICATION CHECKLIST

- [x] ProductController uses `location_batches` for stock display
- [x] SaleController deducts from `location_batches`  
- [x] Autocomplete uses `location_batches` sum
- [x] No code reads from `location_product.qty` for stock calculations
- [x] All DB queries bypass eager-loaded collections (avoid stale data)
- [x] Cache disabled for real-time stock data

---

## 📝 SUMMARY

**Use `location_batches.qty` for ALL stock operations.**

**Ignore `location_product.qty` completely.**

**Always use `DB::table()` queries, never eager-loaded sums.**
