# Transaction Type Standardization Fix

## Issue Fixed
**Error**: "Data truncated for column 'transaction_type'" when creating sale return ledger entries

## Root Cause
The UnifiedLedgerService was using transaction types that were not defined in the database enum, specifically:
- `'sale_return'` (not in enum)
- `'sale_payment'` (not in enum) 
- `'purchase_payment'` (not in enum)
- `'return_payment'` (not in enum)

## Solution Applied
Standardized all transaction types to use existing enum values:

### 1. Payment Transactions → `'payments'`
**All payment-related transactions now use `'payments'`**:
- Sale payments: `'payments'`
- Purchase payments: `'payments'` 
- Return payments: `'payments'`
- Opening balance payments: `'payments'`

The frontend can differentiate based on notes and contact_type.

### 2. Sale Returns → Specific Types
**Sale returns use existing specific types**:
- Returns with original sale: `'sale_return_with_bill'`
- Returns without original sale: `'sale_return_without_bill'`

### 3. Updated Methods in UnifiedLedgerService
- `recordSalePayment()`: Uses `'payments'`
- `recordPurchasePayment()`: Uses `'payments'`
- `recordReturnPayment()`: Uses `'payments'`
- `recordOpeningBalancePayment()`: Uses `'payments'`
- `recordSaleReturn()`: Uses `'sale_return_with_bill'` or `'sale_return_without_bill'` based on `sale_id`

### 4. Updated Ledger Model
Added handling for both sale return transaction types:
```php
case 'sale_return':
case 'sale_return_with_bill':
case 'sale_return_without_bill':
    // Sale return reduces what customer owes us (credit)
    $credit = $data['amount'];
    break;
```

## Database Enum Values (Current)
```php
'opening_balance',
'purchase', 
'purchase_return',
'sale',
'sale_return_with_bill',
'sale_return_without_bill', 
'payments',
'payment',
'return',
'opening_balance_payment'
```

## Debit/Credit Logic Maintained
All payment transactions use `'payments'` transaction type but maintain correct debit/credit logic:
- **Customer payments**: Credit (reduces what customer owes)
- **Supplier payments**: Debit (reduces what we owe supplier)
- **Return payments to customers**: Credit (reduces customer debt)
- **Return payments from suppliers**: Debit (reduces our debt to supplier)

## Result
✅ **No more "Data truncated" errors**
✅ **All transaction types match database enum values**
✅ **Consistent payment handling with single transaction type**
✅ **Frontend can differentiate payment types via notes and contact_type**
✅ **Proper sale return type selection (with/without bill)**