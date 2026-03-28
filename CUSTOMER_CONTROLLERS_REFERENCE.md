# Customer Controllers - Reference Guide

## Three Customer Controllers in Your System

### 1️⃣ **Web Customer Controller** (Main UI)
- **Location**: `App\Http\Controllers\Web\CustomerController`
- **File Path**: [app/Http/Controllers/Web/CustomerController.php](app/Http/Controllers/Web/CustomerController.php)
- **Routes Used**: `/customer*` routes in [routes/web.php](routes/web.php#L140)
- **Methods**:
  - `customer()` - Display customer page
  - `index()` - Get all customers
  - `store()`, `update()`, `destroy()` - CRUD operations
  - `getCreditInfo()` - Get customer credit information
  - **✅ `recordRecoveryPayment()`** - Record bounced cheque recovery (FIXED)
  - `getCreditLimitForCity()`
  - `getCustomersByRoute()`
  - `filterByCities()`
  - `export()`, `import()`, etc.

**Recovery Payment Method**: ✅ FIXED
```php
public function recordRecoveryPayment(Request $request, $customerId)
// Now delegates to ChequeService::recordRecoveryPayment()
// Creates proper ledger entries ✓
```

**Route**: `POST /floating-balance/customer/{id}/recovery-payment`

---

### 2️⃣ **Main Customer Controller** (Legacy/Base)
- **Location**: `App\Http\Controllers\CustomerController`
- **File Path**: [app/Http/Controllers/CustomerController.php](app/Http/Controllers/CustomerController.php)
- **Routes Used**: Currently NOT used in web.php or api.php
- **Status**: ⚠️ **LEGACY - NOT ACTIVELY USED**
- **Methods**:
  - `customer()` - Display customer page
  - `index()` - Get all customers
  - `store()`, `update()`, `destroy()` - CRUD operations
  - `getCustomersWithBouncedCheques()` - List customers with bounced cheques
  - `getCreditLimitForCity()`
  - `getCustomersByRoute()`
  - `filterByCities()`
  - `export()`, `import()`, etc.

**Recovery Payment Method**: ❌ NOT AVAILABLE
- Does NOT have `recordRecoveryPayment()` method
- Does NOT have recovery payment functionality

**Why It Exists**: Appears to be a base configuration class or legacy code retained for reference.

---

### 3️⃣ **API Customer Controller**
- **Location**: `App\Http\Controllers\Api\CustomerController`
- **File Path**: [app/Http/Controllers/Api/CustomerController.php](app/Http/Controllers/Api/CustomerController.php)
- **Routes Used**: `/api/customer*` routes in [routes/api.php](routes/api.php#L206)
- **Methods**:
  - `customer()` - Display customer page
  - `index()` - Get all customers
  - `store()`, `update()`, `destroy()` - CRUD operations
  - `getCreditLimitForCity()`
  - `getCustomersByRoute()`
  - `filterByCities()`
  - etc.

**Recovery Payment Method**: ❌ NOT AVAILABLE
- Does NOT have `recordRecoveryPayment()` method
- Recovery payments are NOT exposed through API
- This is by design - recovery payments are Web UI only

**Current API Routes**: 
```
GET    /api/customer-get-all
GET    /api/customer-edit/{id}
GET    /api/customer-get-by-route/{routeId}
POST   /api/customers/filter-by-cities
POST   /api/customer-store
POST   /api/customer-update/{id}
DELETE /api/customer-delete/{id}
GET    /api/customer-get-by-id/{id}
```

---

## Summary Table

| Feature | Web Controller | Main Controller | API Controller |
|---------|--|:--:|:--:|
| File | Web/CustomerController.php | CustomerController.php | Api/CustomerController.php |
| Actively Used | ✅ YES | ❌ NO | ✅ YES (for CRUD) |
| Recovery Payment | ✅ YES (FIXED) | ❌ NO | ❌ NO |
| CRUD Operations | ✅ YES | ✅ YES | ✅ YES |
| Import/Export | ✅ YES | ✅ YES | ❌ NO |
| Related Models | FloatingBalanceController | - | - |

---

## Recovery Payment Flow (Web UI Only)

### Route Entry Points:

**1. Web Customer Controller** (PRIMARY)
```
POST /floating-balance/customer/{id}/recovery-payment
↓
CustomerController::recordRecoveryPayment()
↓
ChequeService::recordRecoveryPayment()
↓
UnifiedLedgerService::recordFloatingBalanceRecovery()
↓
Ledger::createEntry() ✓ Creates bounce_recovery entry
```

**2. Floating Balance Controller** (ALTERNATIVE)
```
POST /floating-balance/customer/{customerId}/recovery-payment
↓
FloatingBalanceController::recordRecoveryPayment()
↓
ChequeService::recordRecoveryPayment()
↓
UnifiedLedgerService::recordFloatingBalanceRecovery()
↓
Ledger::createEntry() ✓ Creates bounce_recovery entry
```

**3. Bulk Recovery** (Payment Controller)
```
POST /cheque/bulk-recovery-payment
↓
PaymentController::bulkRecoveryPayment()
↓
Creates multiple recovery payments
↓
UnifiedLedgerService::recordFloatingBalanceRecovery()
↓
Ledger entries created for each ✓
```

---

## Key Points

### ✅ FULLY FUNCTIONAL:
- **Web\CustomerController** - Handles all customer operations + recovery payments
- **Api\CustomerController** - Handles CRUD operations only (recovery not exposed in API)
- **FloatingBalanceController** - Dedicated floating balance management
- **PaymentController** - Bulk recovery payment processing

### ❌ NOT USED:
- **Main CustomerController** - Legacy code, not referenced in routing

### 🔧 FIX APPLIED:
- **Web\CustomerController::recordRecoveryPayment()** - ✅ NOW creates proper ledger entries via ChequeService

---

## Verification Checklist

✅ Web\CustomerController has recovery payment method
✅ Web\CustomerController uses ChequeService (creates ledger entries)
✅ FloatingBalanceController already uses ChequeService
✅ Bulk recovery in PaymentController creates ledger entries
✅ API controller has NO recovery payment method (correct - Web UI only)
✅ Main CustomerController is not used in routing

**Conclusion**: All actively used controllers are now properly configured with ledger entry creation for bounced cheque recoveries.

