# Purchase Controller Ledger Fix Summary

## Issues Fixed

### 1. **Balance Calculation Method**

**Before (Incorrect):**
```php
private function calculateNewBalance($userId, $amount, $type)
{
    $lastLedger = Ledger::where('user_id', $userId)->where('contact_type', 'supplier')->orderBy('transaction_date', 'desc')->first();
    $previousBalance = $lastLedger ? $lastLedger->balance : 0;

    return $type === 'debit' ? $previousBalance + $amount : $previousBalance - $amount;
}
```

**After (Correct):**
```php
private function calculateNewBalance($userId, $debitAmount, $creditAmount)
{
    $lastLedger = Ledger::where('user_id', $userId)
        ->where('contact_type', 'supplier')
        ->orderBy('transaction_date', 'desc')
        ->orderBy('id', 'desc')
        ->first();

    $previousBalance = $lastLedger ? $lastLedger->balance : 0;
    return $previousBalance + $debitAmount - $creditAmount;
}
```

### 2. **Ledger Entry Creation in storeOrUpdate**

**Before (Incorrect):**
```php
// Insert ledger entry for the purchase
Ledger::create([
    'transaction_date' => $request->purchase_date,
    'reference_no' => $purchase->reference_no,
    'transaction_type' => 'purchase',
    'debit' => $request->final_total,
    'credit' => 0,
    'balance' => $this->calculateNewBalance($request->supplier_id, $request->final_total, 'debit'),
    'contact_type' => 'supplier',
    'user_id' => $request->supplier_id,
]);

// Insert ledger entry for the payment if paid_amount is provided
if ($request->paid_amount > 0) {
    Ledger::create([
        'transaction_date' => $request->paid_date ? \Carbon\Carbon::parse($request->paid_date) : now(),
        'reference_no' => $purchase->reference_no,
        'transaction_type' => 'payments',
        'debit' => 0,
        'credit' => $request->paid_amount,
        'balance' => $this->calculateNewBalance($request->supplier_id, $request->paid_amount, 'credit'),
        'contact_type' => 'supplier',
        'user_id' => $request->supplier_id,
    ]);
}
```

**After (Correct):**
```php
// Clean up existing ledger entries if this is an update
if ($purchaseId) {
    Ledger::where('reference_no', $purchase->reference_no)
        ->where('contact_type', 'supplier')
        ->delete();
}

// Insert ledger entry for the purchase
Ledger::create([
    'transaction_date' => $request->purchase_date,
    'reference_no' => $purchase->reference_no,
    'transaction_type' => 'purchase',
    'debit' => $request->final_total,
    'credit' => 0,
    'balance' => $this->calculateNewBalance($request->supplier_id, $request->final_total, 0),
    'contact_type' => 'supplier',
    'user_id' => $request->supplier_id,
]);

// Handle payment if paid_amount is provided
if ($request->paid_amount > 0) {
    $this->handlePayment($request, $purchase);
}
```

### 3. **Payment Handling with Ledger**

**Before (Missing ledger entry):**
```php
private function handlePayment($request, $purchase)
{
    // ... payment creation logic ...
    
    Payment::create([...]);
    
    // ... payment status update ...
    // NO LEDGER ENTRY CREATED
}
```

**After (Correct with ledger entry):**
```php
private function handlePayment($request, $purchase)
{
    // ... payment creation logic ...
    
    $payment = Payment::create([...]);

    // Create ledger entry for the payment
    Ledger::create([
        'transaction_date' => $payment->payment_date,
        'reference_no' => $purchase->reference_no,
        'transaction_type' => 'payments',
        'debit' => 0,
        'credit' => $paidAmount,
        'balance' => $this->calculateNewBalance($purchase->supplier_id, 0, $paidAmount),
        'contact_type' => 'supplier',
        'user_id' => $purchase->supplier_id,
    ]);
    
    // ... payment status update ...
}
```

## Key Improvements

1. **Consistent Balance Calculation**: Now matches the Sales Controller pattern
2. **Proper Ledger Cleanup**: Removes old ledger entries when updating purchases
3. **Correct Accounting Logic**: 
   - Purchase increases supplier balance (debit)
   - Payment decreases supplier balance (credit)
4. **Payment Ledger Integration**: Payments now properly create ledger entries
5. **Improved Order**: Ledger entries are created after product processing but before balance updates

## Testing

The fixes ensure that:
- Supplier balances are correctly maintained
- Ledger entries follow standard accounting principles
- Updates don't create duplicate ledger entries
- The system matches the proven Sales Controller pattern

## Formula

**Supplier Balance = Previous Balance + Purchases - Payments**

Where:
- Purchases = Debit entries (increase balance owed to supplier)
- Payments = Credit entries (decrease balance owed to supplier)
