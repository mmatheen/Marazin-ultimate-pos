# Reference Number Fix Summary

## Issue Fixed
**Error**: "Required field reference_no is missing" when creating sale return ledger entries

## Root Cause
The `UnifiedLedgerService` methods were trying to use non-existent or null reference number fields:

1. **Sale Returns**: Used `$saleReturn->return_no` (field doesn't exist)
2. **Purchases**: Used `$purchase->reference_no` directly (could be null)
3. **Sales**: Used `$sale->invoice_no` directly (could be null)

## Solutions Implemented

### 1. Fixed recordSaleReturn()
**Before**:
```php
'reference_no' => $saleReturn->return_no, // Field doesn't exist!
```

**After**:
```php
$referenceNo = $saleReturn->invoice_number ?: 'SR-' . $saleReturn->id;
'reference_no' => $referenceNo,
```

### 2. Fixed recordSale()
**Before**:
```php
'reference_no' => $sale->invoice_no, // Could be null
```

**After**:
```php
$referenceNo = $sale->invoice_no ?: 'INV-' . $sale->id;
'reference_no' => $referenceNo,
```

### 3. Fixed recordPurchase()
**Before**:
```php
'reference_no' => $purchase->reference_no, // Could be null
```

**After**:
```php
$referenceNo = $purchase->reference_no ?: 'PUR-' . $purchase->id;
'reference_no' => $referenceNo,
```

### 4. Fixed recordPurchaseReturn()
**Before**:
```php
'reference_no' => $purchaseReturn->return_no, // Wrong field name
```

**After**:
```php
$referenceNo = $purchaseReturn->reference_no ?: 'PR-' . $purchaseReturn->id;
'reference_no' => $referenceNo,
```

## Fallback Reference Number Patterns
- Sales: `INV-{id}` (e.g., INV-123)
- Purchases: `PUR-{id}` (e.g., PUR-456)
- Sale Returns: `SR-{id}` (e.g., SR-789)
- Purchase Returns: `PR-{id}` (e.g., PR-101)

## Result
✅ **No more "Required field reference_no is missing" errors**
✅ **All ledger entries now have proper reference numbers**
✅ **Fallback reference numbers generated automatically when primary field is null**
✅ **Consistent reference number handling across all transaction types**

## Files Modified
- `app/Services/UnifiedLedgerService.php`: Fixed all 4 record methods with proper fallback logic