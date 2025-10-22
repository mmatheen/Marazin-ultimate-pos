# Sale Order POS - Testing Checklist

## Pre-Testing Setup

### 1. Database Migration
```bash
php artisan migrate
```
Expected: `2025_10_22_000001_add_sale_order_fields_to_sales_table` migration runs successfully

### 2. Clear Cache
```bash
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

### 3. Test User Setup
- [ ] Create test user with "create sale" permission
- [ ] Create test sales rep linked to user
- [ ] Create test customers (at least 2 non-walk-in customers)

---

## UI Testing

### Button Visibility
- [ ] Sale Order button appears in POS (after Draft button)
- [ ] Button has green outline styling
- [ ] Button has shopping cart icon
- [ ] Button shows text "Sale Order"
- [ ] Button only visible with "create sale" permission
- [ ] Button hidden for users without permission

### Button Click
- [ ] Button clickable and not disabled initially
- [ ] Clicking opens Sale Order modal
- [ ] Modal has correct title "Create Sale Order"
- [ ] Modal shows Expected Delivery Date field
- [ ] Modal shows Order Notes textarea
- [ ] Modal has Cancel and Create buttons

### Date Field
- [ ] Date field is type="date"
- [ ] Date field shows calendar picker
- [ ] Minimum date is today (cannot select past)
- [ ] Can select today's date
- [ ] Can select future dates
- [ ] Field is required (HTML5 validation)

### Notes Field
- [ ] Textarea shows placeholder text
- [ ] Can enter multiple lines
- [ ] Field is optional (can be empty)
- [ ] Character limit reasonable (no restriction currently)

---

## Validation Testing

### 1. Empty Cart Validation
**Steps:**
1. Open POS without adding any products
2. Click "Sale Order" button

**Expected:**
- [ ] Error toastr: "Please add at least one product to create a sale order."
- [ ] Modal does NOT open
- [ ] No API call made

### 2. Walk-in Customer Validation
**Steps:**
1. Select "Walk-in Customer" from dropdown
2. Add at least one product to cart
3. Click "Sale Order" button

**Expected:**
- [ ] Error toastr: "Sale Orders cannot be created for Walk-In customers..."
- [ ] Modal does NOT open
- [ ] No API call made

### 3. No Delivery Date Validation
**Steps:**
1. Select valid customer
2. Add products to cart
3. Click "Sale Order" button
4. Leave delivery date empty
5. Click "Create Sale Order"

**Expected:**
- [ ] Error toastr: "Please select an expected delivery date."
- [ ] Modal stays open
- [ ] No API call made

### 4. Past Date Validation
**Steps:**
1. Follow steps 1-3 above
2. Manually set date to yesterday (if browser allows)
3. Click "Create Sale Order"

**Expected:**
- [ ] Error toastr: "Expected delivery date cannot be in the past."
- [ ] Modal stays open
- [ ] No API call made

---

## Functional Testing

### 1. Successful Sale Order Creation
**Steps:**
1. Select valid customer (not walk-in)
2. Add 2-3 products with quantities
3. Click "Sale Order" button
4. Enter future delivery date (e.g., 7 days from today)
5. Enter notes: "Test order - handle carefully"
6. Click "Create Sale Order"

**Expected:**
- [ ] Success toastr appears with order number
- [ ] Message format: "Sale Order created successfully! Order Number: SO-YYYY-NNNN"
- [ ] Toastr has progress bar and 5 second timeout
- [ ] Success sound plays
- [ ] Modal closes automatically
- [ ] POS form resets (cart cleared)
- [ ] Customer resets to walk-in
- [ ] NO receipt prints
- [ ] Recent transactions refreshes

### 2. Order Number Generation
**Create 3 Sale Orders in sequence:**

**First Order:**
- [ ] Order number: SO-2025-0001 (or current year)

**Second Order:**
- [ ] Order number: SO-2025-0002

**Third Order:**
- [ ] Order number: SO-2025-0003

**Expected:**
- [ ] Sequential numbering
- [ ] Year in format matches current year
- [ ] No duplicates

### 3. Database Record Validation
**After creating a sale order, check database:**

```sql
SELECT * FROM sales WHERE transaction_type = 'sale_order' ORDER BY id DESC LIMIT 1;
```

**Check these fields:**
- [ ] `transaction_type` = 'sale_order'
- [ ] `order_number` = 'SO-YYYY-NNNN'
- [ ] `order_status` = 'pending'
- [ ] `order_date` = today's date
- [ ] `expected_delivery_date` = date you entered
- [ ] `order_notes` = notes you entered
- [ ] `user_id` = your logged-in user ID
- [ ] `customer_id` = selected customer ID
- [ ] `invoice_no` = NULL
- [ ] `status` = 'final'

**Check sales_products table:**
```sql
SELECT * FROM sales_products WHERE sale_id = [your_sale_id];
```

- [ ] Products saved correctly
- [ ] Quantities match
- [ ] Prices match
- [ ] Batch IDs saved

### 4. Stock NOT Reduced
**Steps:**
1. Note current stock of a product: _______ units
2. Create sale order with 5 units of that product
3. Check stock again

**Expected:**
- [ ] Stock remains SAME as before
- [ ] No stock_movements record created for sale order
- [ ] Batch quantities unchanged

### 5. No Payment Created
**After creating sale order:**
```sql
SELECT * FROM payments WHERE reference_id = [your_sale_id] AND payment_type = 'sale';
```

**Expected:**
- [ ] Zero rows returned
- [ ] No payment records for sale order

---

## Integration Testing

### 6. Multiple Products with Different Units
**Steps:**
1. Add product with decimal units (e.g., 2.5 kg)
2. Add product with integer units (e.g., 10 pieces)
3. Add product with IMEI (if applicable)
4. Create sale order

**Expected:**
- [ ] All products saved correctly
- [ ] Decimal quantities handled properly
- [ ] IMEI numbers saved (if any)
- [ ] Subtotals calculated correctly

### 7. With Discount
**Steps:**
1. Add products to cart
2. Apply global discount (e.g., Rs. 500 or 10%)
3. Create sale order

**Expected:**
- [ ] Discount saved in `discount_amount` field
- [ ] Discount type saved correctly
- [ ] Final total calculated with discount
- [ ] Sale order creates successfully

### 8. Different Locations
**If multi-location setup:**

**Location 1:**
- [ ] Create sale order
- [ ] Order number: SO-2025-0001

**Location 2:**
- [ ] Create sale order
- [ ] Order number: SO-2025-0001 (independent sequence)

**Expected:**
- [ ] Each location has independent numbering
- [ ] No conflicts between locations

---

## Error Handling Testing

### 9. Network Error Simulation
**Steps:**
1. Open browser DevTools → Network tab
2. Set throttling to "Offline"
3. Try creating sale order

**Expected:**
- [ ] Error message appears
- [ ] Modal stays open
- [ ] Form data not lost
- [ ] Can retry when online

### 10. Concurrent Creation
**Steps:**
1. Open POS in two browser tabs
2. Create sale order in Tab 1 (Order: SO-2025-0005)
3. Quickly create sale order in Tab 2

**Expected:**
- [ ] No duplicate order numbers
- [ ] Both orders created successfully
- [ ] Order numbers sequential (0005, 0006)

---

## Permission Testing

### 11. Without "create sale" Permission
**Steps:**
1. Login with user without "create sale" permission
2. Navigate to POS

**Expected:**
- [ ] Sale Order button NOT visible
- [ ] Other buttons visible based on permissions
- [ ] No console errors

### 12. With "create sale" Permission
**Steps:**
1. Login with user having "create sale" permission
2. Navigate to POS

**Expected:**
- [ ] Sale Order button visible
- [ ] Can create sale orders
- [ ] user_id saved correctly

---

## Browser Compatibility Testing

Test in these browsers:

### Chrome
- [ ] Button displays correctly
- [ ] Modal works
- [ ] Date picker works
- [ ] Sale order creates successfully

### Firefox
- [ ] Button displays correctly
- [ ] Modal works
- [ ] Date picker works
- [ ] Sale order creates successfully

### Edge
- [ ] Button displays correctly
- [ ] Modal works
- [ ] Date picker works
- [ ] Sale order creates successfully

### Safari (if available)
- [ ] Button displays correctly
- [ ] Modal works
- [ ] Date picker works
- [ ] Sale order creates successfully

### Mobile Browser (Chrome/Safari)
- [ ] Button responsive
- [ ] Modal usable on mobile
- [ ] Date picker mobile-friendly
- [ ] Touch interactions work

---

## Performance Testing

### 13. Large Order (Many Products)
**Steps:**
1. Add 20+ different products to cart
2. Create sale order

**Expected:**
- [ ] No performance issues
- [ ] Response within 3 seconds
- [ ] All products saved correctly

### 14. Rapid Multiple Orders
**Steps:**
1. Create 10 sale orders in quick succession

**Expected:**
- [ ] All orders created successfully
- [ ] Sequential numbering maintained
- [ ] No duplicates
- [ ] No database locks or errors

---

## Regression Testing

### 15. Regular Sales Still Work
**Steps:**
1. Create a normal POS sale (not sale order)
2. Select customer, add products
3. Click "Finalize Sale"
4. Process payment

**Expected:**
- [ ] Invoice number generates correctly
- [ ] Stock reduces
- [ ] Payment processed
- [ ] Receipt prints
- [ ] No interference from sale order feature

### 16. Draft Sales Still Work
**Steps:**
1. Add products
2. Click "Draft" button

**Expected:**
- [ ] Draft created with D/YYYY/NNNN format
- [ ] No sale order fields set
- [ ] Normal draft functionality

### 17. Quotation Still Works
**Steps:**
1. Add products
2. Click "Quotation" button

**Expected:**
- [ ] Quotation created with Q/YYYY/NNNN format
- [ ] No sale order fields set
- [ ] Normal quotation functionality

---

## User Acceptance Testing (UAT)

### 18. Sales Rep Workflow
**Scenario:** Sales rep visits customer, takes order

**Steps:**
1. Sales rep opens POS on tablet/phone
2. Selects customer from list
3. Customer requests: "I need 10 boxes of Product A"
4. Sales rep adds product, quantity 10
5. Customer: "I need it by next Friday"
6. Sales rep sets delivery date to next Friday
7. Customer: "Please call before delivery"
8. Sales rep adds note: "Call before delivery"
9. Creates sale order

**Expected:**
- [ ] Workflow smooth and intuitive
- [ ] Sales rep can complete in under 2 minutes
- [ ] Order number clearly visible
- [ ] Sales rep can note down order number

### 19. Shop Staff Review
**Scenario:** Shop staff reviews pending orders

**Steps:**
1. Sales rep creates 3 sale orders
2. Shop staff needs to see pending orders

**Expected:**
- [ ] Sale orders visible in database
- [ ] Can query by order_status = 'pending'
- [ ] Can see which sales rep created each order (user_id)
- [ ] *(Note: List view page not yet implemented - manual check)*

---

## Documentation Testing

### 20. User Guide Accuracy
- [ ] Follow SALE_ORDER_POS_INTEGRATION.md step by step
- [ ] All steps accurate
- [ ] Screenshots match (if added)
- [ ] No missing information

### 21. Tamil Guide Usability
- [ ] Tamil text displays correctly
- [ ] Translations accurate
- [ ] Examples clear
- [ ] Error messages match actual system

---

## Final Sign-Off

### Development Team
- [ ] All code changes reviewed
- [ ] No console errors in browser
- [ ] No PHP errors in logs
- [ ] Database queries optimized
- [ ] Code follows project standards

### QA Team
- [ ] All test cases passed
- [ ] Edge cases handled
- [ ] Error messages user-friendly
- [ ] Performance acceptable

### Product Owner
- [ ] Feature matches requirements
- [ ] User experience satisfactory
- [ ] Ready for production deployment

---

## Test Results Summary

**Date Tested:** _________________
**Tested By:** _________________
**Environment:** _________________

**Total Test Cases:** 100+
**Passed:** _______
**Failed:** _______
**Blocked:** _______

**Critical Issues Found:**
1. ___________________________
2. ___________________________

**Minor Issues Found:**
1. ___________________________
2. ___________________________

**Overall Status:** ☐ PASS | ☐ FAIL | ☐ CONDITIONAL PASS

**Notes:**
_________________________________________
_________________________________________
_________________________________________

**Approved By:** _________________
**Date:** _________________
