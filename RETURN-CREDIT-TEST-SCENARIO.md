# Return Credit Payment Test Scenario

## Test Case: Return Credit Applied to Sales

### Setup:
- Customer: Aasath (ID 919)
- Return: SR-0013 (Rs. 13,320.00)
- Sales Due:
  - ATF-083 (ID 295): Rs. 47,360.00
  - PDX-006 (ID 572): Rs. 80,520.00

### Expected Behavior:

#### Scenario 1: Return Applied to ATF-083 Only
**Action:**
1. Select return SR-0013 (Rs. 13,320.00) → Action: "Apply to Sales"
2. Return credit auto-allocates to ATF-083 (oldest bill)
3. Add ATF-083 to payment → Amount should show Rs. 34,040.00 (47,360 - 13,320)
4. Add PDX-006 to payment → Amount should show Rs. 80,520.00 (no return credit)
5. Submit with Cash payment

**Expected Payments Table:**
```
ID  Amount      Method              Type                Reference   Notes
--- ----------- ------------------- ------------------- ----------- ----------------------------------
1   13,320.00   advance_adjustment  sale_return_...     SR-0013     Return credit for SR-0013
2   13,320.00   advance_adjustment  sale                ATF-083     Return credit applied to ATF-083
3   34,040.00   cash                sale                ATF-083     Cash payment
4   80,520.00   cash                sale                PDX-006     Cash payment
```

**Expected Sales Table:**
```
ID   Invoice   Final Total   Total Paid   Total Due   Status
---- --------- ------------- ------------ ----------- ------
295  ATF-083   47,360.00     47,360.00    0.00        Paid
572  PDX-006   80,520.00     80,520.00    0.00        Paid
```

**Expected Sales Returns Table:**
```
ID   Invoice   Return Total   Total Paid   Total Due   Status
---- --------- -------------- ------------ ----------- ------
13   SR-0013   13,320.00      13,320.00    0.00        Paid
```

**Expected Ledger Entries:**
```
Contact  Transaction Type  Debit       Credit      Notes
-------- ----------------- ----------- ----------- ----------------------------------
919      payments          13,320.00   0.00        Return credit applied
919      payments          34,040.00   0.00        Cash payment for ATF-083
919      payments          80,520.00   0.00        Cash payment for PDX-006
```

---

### Current Bug (From Database):

**Actual Payments Table:**
```
ID  Amount      Method              Type                Reference   Notes
--- ----------- ------------------- ------------------- ----------- ----------------------------------
243 13,320.00   advance_adjustment  sale_return_...     SR-0013     ✓ Correct
244 13,320.00   advance_adjustment  sale                ATF-083     ✓ Correct
245 34,040.00   cash                sale                ATF-083     ✓ Correct
246 13,320.00   cash                sale                PDX-006     ✗ WRONG! Should be 80,520.00
```

**Problem:** Payment 246 shows Rs. 13,320 instead of Rs. 80,520

**Root Cause:**
The bill amount in `payment_groups` is being sent as the REDUCED amount (after return credit), but it's being applied to the WRONG bill (PDX-006 instead of being skipped or showing full amount).

---

## Debugging Steps:

### 1. Check Console Log (Frontend)
Open browser console when submitting payment. Look for:
```javascript
Submitting payment with: {
    selected_returns: [{return_id: 13, amount: 13320, action: "apply_to_sales"}],
    bill_return_allocations: {295: 13320},  // ATF-083 gets 13320 credit
    payment_groups: [{
        bills: [
            {sale_id: 295, amount: 34040},  // Should be ONLY cash portion
            {sale_id: 572, amount: 80520}   // Should be FULL amount (no credit)
        ]
    }]
}
```

### 2. Verify Frontend Calculation
Check `billReturnCreditAllocations` object:
```javascript
window.billReturnCreditAllocations = {
    295: 13320  // Only bill 295 should have return credit
    // 572 should NOT be in this object
}
```

### 3. Check Bill Allocation Amounts
When adding bills to payment:
- **Bill 295 (ATF-083):**
  - Original due: Rs. 47,360
  - Return credit: Rs. 13,320
  - Amount to pay: Rs. 34,040 ✓
  
- **Bill 572 (PDX-006):**
  - Original due: Rs. 80,520
  - Return credit: Rs. 0
  - Amount to pay: Rs. 80,520 ✓

---

## Fix Required:

### Issue Location:
The problem is likely in how `bill_return_allocations` are being calculated or how bills are being added to `payment_groups`.

### Possible Causes:

**1. FIFO Allocation Error:**
```javascript
// If FIFO is allocating incorrectly:
autoAllocateReturnCreditsToSales(13320) {
    // Should allocate to OLDEST bill only
    // Bill 295 (ATF-083) = oldest
    // Bill 572 (PDX-006) = newer, should get 0 credit
}
```

**2. Bill Amount Calculation:**
```javascript
// When adding bill to payment, check:
const returnCredit = billReturnCreditAllocations[billId] || 0;
const remainingAmount = bill.total_due - returnCredit;

// This should give:
// Bill 295: 47360 - 13320 = 34040 ✓
// Bill 572: 80520 - 0 = 80520 ✓
```

**3. Payment Groups Submission:**
The `payment_groups[].bills[]` array should contain:
```javascript
bills: [
    {sale_id: 295, amount: 34040},  // Cash only
    {sale_id: 572, amount: 80520}   // Full amount
]
```

---

## Testing Checklist:

- [ ] Console shows correct `bill_return_allocations`
- [ ] Console shows correct `payment_groups` amounts
- [ ] Database: 4 payments created (1 return, 1 return-to-sale, 2 cash)
- [ ] Database: Sales have correct `total_paid`
- [ ] Database: Sales have correct `payment_status`
- [ ] Database: Sales return has `payment_status = 'Paid'`
- [ ] Database: Ledger entries are correct
- [ ] UI: Bills show correct "Return Credit Applied" badges
- [ ] UI: Bill amounts update when return action changes

---

## Quick Test Command:

```sql
-- Check payments for customer 919
SELECT 
    id, 
    amount, 
    payment_method, 
    payment_type, 
    reference_id,
    notes 
FROM payments 
WHERE customer_id = 919 
ORDER BY id DESC 
LIMIT 10;

-- Check sales total_paid
SELECT 
    id, 
    invoice_no, 
    final_total, 
    total_paid, 
    total_due, 
    payment_status 
FROM sales 
WHERE customer_id = 919;

-- Check sales_returns
SELECT 
    id, 
    invoice_number, 
    return_total, 
    total_paid, 
    total_due, 
    payment_status 
FROM sales_returns 
WHERE customer_id = 919;
```

---

**Next Steps:**
1. Clear the test data (delete payments 243-246)
2. Reset sales total_paid to 0
3. Test again with console open
4. Check the logged values match expected values
