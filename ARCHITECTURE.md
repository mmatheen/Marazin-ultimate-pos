# POS System Architecture

## 🏗️ System Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                     BROWSER (Client-Side)                        │
│                                                                  │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │                    main.js (Orchestrator)                   │ │
│  │  • DOMContentLoaded initialization                          │ │
│  │  • Component coordination                                   │ │
│  │  • Global event handling                                    │ │
│  └─────────────┬────────────────────────────────┬──────────────┘ │
│                │                                │                 │
│    ┌───────────▼──────────┐       ┌────────────▼────────────┐   │
│    │   UI Components      │       │    Business Logic        │   │
│    │  (15 files)          │       │    (Models - 3 files)    │   │
│    │                      │       │                          │   │
│    │  • BillingTable      │◄──────┤  • Product Model         │   │
│    │  • ProductGrid       │       │  • Customer Model        │   │
│    │  • SearchAutocomp... │       │  • Sale Model            │   │
│    │  • ImeiModal         │       │                          │   │
│    │  • BatchPriceModal   │       └──────────┬───────────────┘   │
│    │  • CustomerSelector  │                  │                   │
│    │  • LocationSelector  │       ┌──────────▼───────────────┐   │
│    │  • PaymentModal      │       │   Feature Modules        │   │
│    │  • ShippingModal     │       │   (10 files)             │   │
│    │  • MobileGrids       │       │                          │   │
│    │  • RecentTrans...    │       │  • salesRepModule        │   │
│    │  • ProductModal      │       │  • barcodeScannerMod..   │   │
│    │  • JobTicketModal    │◄──────┤  • quickAddModule        │   │
│    │  • MobileMenu        │       │  • priceHistoryModule    │   │
│    └──────┬───────────────┘       │  • suspendSalesModule    │   │
│           │                       │  • jobTicketModule       │   │
│           │                       │  • saleOrderModule       │   │
│           │                       │  • draftModule           │   │
│           │                       │  • quotationModule       │   │
│           │                       │  • recentTransactions    │   │
│    ┌──────▼───────────────┐       └──────────┬───────────────┘   │
│    │   Validators         │                  │                   │
│    │   (3 files)          │                  │                   │
│    │                      │       ┌──────────▼───────────────┐   │
│    │  • quantityValidator │       │   Utilities              │   │
│    │  • priceValidator    │◄──────┤   (5 files)              │   │
│    │  • paymentValidator  │       │                          │   │
│    └──────┬───────────────┘       │  • printHelper           │   │
│           │                       │  • stockHelper           │   │
│           │                       │  • errorLogger           │   │
│    ┌──────▼───────────────┐       │  • calculatorHelper      │   │
│    │   Event System       │       │  • notificationHelper    │   │
│    │   (2 files)          │       └──────────────────────────┘   │
│    │                      │                                      │
│    │  • eventBus          │                                      │
│    │  • eventHandlers     │                                      │
│    └──────┬───────────────┘                                      │
│           │                                                      │
│    ┌──────▼───────────────────────────────────────────────────┐ │
│    │              Core Infrastructure                          │ │
│    │                                                            │ │
│    │  ┌─────────────┐  ┌──────────────┐  ┌─────────────────┐ │ │
│    │  │ config.js   │  │ constants.js │  │   utils.js      │ │ │
│    │  │ • POSState  │  │ • ERROR_MSG  │  │ • safeParseFloat│ │ │
│    │  │ • POSConfig │  │ • SUCCESS_MSG│  │ • formatAmount  │ │ │
│    │  │ • shipping  │  │ • PAYMENT... │  │ • createSafeImg │ │ │
│    │  │ • autocomplete│ │ • SALE_STATUS│ │ • debounce      │ │ │
│    │  └─────────────┘  └──────────────┘  │ • showToast     │ │ │
│    │                                      │ • cleanupModal  │ │ │
│    │                                      └─────────────────┘ │ │
│    └────────────────────────────────────────────────────────── │ │
│                                                                  │
│    ┌──────────────────────────────────────────────────────────┐ │
│    │                Cache Management                           │ │
│    │                                                            │ │
│    │  ┌─────────────────────────────────────────────────────┐ │ │
│    │  │            cacheManager.js                           │ │ │
│    │  │  • Customer Cache (5 min TTL)                        │ │ │
│    │  │  • Static Data Cache (10 min TTL)                    │ │ │
│    │  │  • Search Cache (30 sec TTL)                         │ │ │
│    │  │  • Customer Price Cache                              │ │ │
│    │  │  • Cross-tab Synchronization (LocalStorage events)   │ │ │
│    │  └─────────────────────────────────────────────────────┘ │ │
│    └──────────────────────────────────────────────────────────┘ │
│                                                                  │
│    ┌──────────────────────────────────────────────────────────┐ │
│    │                 API Layer (9 Services)                    │ │
│    │                                                            │ │
│    │  ┌────────────────────────────────────────────────────┐  │ │
│    │  │             apiClient.js (Base)                     │  │ │
│    │  │  • CSRF Token Management                            │  │ │
│    │  │  • Retry Logic (429 Rate Limiting)                  │  │ │
│    │  │  • Error Handling (419 Session Expired)             │  │ │
│    │  │  • Exponential Backoff                              │  │ │
│    │  └────────────────┬───────────────────────────────────┘  │ │
│    │                   │                                       │ │
│    │  ┌────────────────▼───────────────────────────────────┐  │ │
│    │  │  productService  │  customerService  │  saleService │  │ │
│    │  │  locationService │  categoryService  │ brandService │  │ │
│    │  │  salesRepService │   imeiService    │shippingServ...│  │ │
│    │  └──────────────────────────────────────────────────────┘ │ │
│    └───────────────────────────┬──────────────────────────────┘ │
│                                │                                 │
└────────────────────────────────┼─────────────────────────────────┘
                                 │
                                 │ HTTP/AJAX
                                 │
┌────────────────────────────────▼─────────────────────────────────┐
│                    SERVER (Laravel Backend)                       │
│                                                                  │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │                    API Routes                               │ │
│  │                                                              │ │
│  │  /products/*        → ProductController                     │ │
│  │  /customers/*       → CustomerController                    │ │
│  │  /sales/*           → SaleController                        │ │
│  │  /locations/*       → LocationController                    │ │
│  │  /categories/*      → CategoryController                    │ │
│  │  /brands/*          → BrandController                       │ │
│  │  /sales-rep/*       → SalesRepController                    │ │
│  │  /imeis/*           → ImeiController                        │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                  │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │                    Database                                 │ │
│  │                                                              │ │
│  │  • products          • customers       • sales              │ │
│  │  • product_stock     • locations       • sale_details       │ │
│  │  • batches           • categories      • imeis              │ │
│  │  • brands            • routes          • vehicles           │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘
```

## 📊 Data Flow Examples

### Flow 1: Adding Product to Billing Table

```
User Types Product Name
        │
        ▼
SearchAutocomplete.js
        │
        ├─► Check searchCache ───► Cache Hit? ───► Return Results
        │                                 │ No
        ▼                                 ▼
productService.searchProducts()
        │
        ▼
apiClient.get('/products/search')
        │
        ▼
Laravel ProductController
        │
        ▼
Database Query (with stock filter)
        │
        ▼
JSON Response
        │
        ▼
Cache Result (30 sec TTL)
        │
        ▼
Display Dropdown Results
        │
        ▼
User Selects Product
        │
        ▼
BillingTable.addProduct()
        │
        ├─► getCurrentCustomer() ───► customerCache
        │                                 │
        ├─► getCustomerTypePrice()        │
        │        │                        │
        │        └─► Product.calculatePrice()
        │                                 │
        ▼                                 ▼
Generate Table Row HTML
        │
        ▼
Attach Event Listeners
        │
        ├─► Quantity Change ───► updateTotals()
        ├─► Price Change ───► priceValidator.validate()
        └─► Discount Change ───► updateTotals()
```

### Flow 2: Sales Rep Customer Filtering

```
User Logs In
        │
        ▼
main.js → checkSalesRepStatus()
        │
        ▼
salesRepService.getMyAssignments()
        │
        ▼
Check User Role (DB: roles table)
        │
        ├─► Not Sales Rep ───► Show All Buttons
        │
        └─► Is Sales Rep
                │
                ▼
        Show Vehicle/Route Selection Modal
                │
                ▼
        User Selects Vehicle & Route
                │
                ▼
        Store in localStorage
                │
                ├─► restrictLocationAccess()
                │   └─► Auto-select vehicle sublocation
                │
                └─► filterCustomersByRoute()
                        │
                        ▼
                customerService.filterCustomersByCities()
                        │
                        ▼
                Get Route Cities
                        │
                        ▼
                Filter Customers by City
                        │
                        ▼
                Populate Customer Dropdown
                        │
                        └─► Mark as filtered (prevent re-filter)
```

### Flow 3: Sale Creation (Cash Payment)

```
User Clicks "Cash" Button
        │
        ▼
preventDoubleClick() ───► Disable Button
        │
        ▼
Gather Sale Data
        │
        ├─► Billing Table Rows
        ├─► Customer Info
        ├─► Location Info
        ├─► Shipping Data
        └─► Payment Method
        │
        ▼
Validate Data
        │
        ├─► quantityValidator.validateAll()
        ├─► priceValidator.validate()
        └─► paymentValidator.validate()
        │
        ▼
saleService.createSale(data)
        │
        ▼
apiClient.post('/sales/create')
        │
        ▼
Laravel SaleController
        │
        ├─► Create Sale Record
        ├─► Create Sale Details
        ├─► Update Stock Quantities
        ├─► Update Customer Balance (if credit)
        └─► Mark IMEI as sold (if applicable)
        │
        ▼
Transaction Commit
        │
        ▼
Return Sale ID & Invoice Number
        │
        ▼
Clear Billing Table
        │
        ├─► Reset Customer to "Please Select"
        ├─► Clear all rows
        └─► Reset totals
        │
        ▼
Show Success Message
        │
        └─► Optional: Open Print Window
```

### Flow 4: Edit Mode (Preserve Original Prices)

```
URL: /pos/edit/12345
        │
        ▼
main.js → checkEditMode()
        │
        ├─► Set POSState.isEditing = true
        └─► Set POSState.currentEditingSaleId = 12345
        │
        ▼
saleService.fetchSaleById(12345)
        │
        ▼
apiClient.get('/sales/12345')
        │
        ▼
Laravel SaleController → getSale()
        │
        ▼
Database: Fetch sale + sale_details + imeis
        │
        ▼
Return Complete Sale Data
        │
        ▼
Populate UI
        │
        ├─► Set Customer Dropdown
        ├─► Set Location Dropdown
        ├─► Set Date/Reference
        └─► Load Sale Details into Billing Table
                │
                ▼
        For Each Sale Detail:
                │
                ├─► Get Original Price (from DB)
                ├─► Get Original Discount (from DB)
                ├─► Get Original Quantity (from DB)
                └─► Get IMEIs (if applicable)
                │
                ▼
        BillingTable.addProduct()
                │
                ├─► Skip customer price recalculation
                ├─► Use original_price from DB
                ├─► Use original_discount from DB
                └─► Merge existing IMEIs with available
                │
                ▼
        Disable Draft/Quotation buttons (if finalized)
                │
                ▼
        updateTotals() → Recalculate from loaded data
```

## 🔄 Cache Lifecycle

```
┌─────────────────────────────────────────────────────────────┐
│                    Cache Strategy                           │
└─────────────────────────────────────────────────────────────┘

Customer Cache (5 min TTL)
├─► Set: When customer fetched from API
├─► Get: Before API call
├─► Clear: On customer change
└─► Invalidate: On cache refresh

Static Data Cache (10 min TTL)
├─► Categories (rarely change)
├─► Brands (rarely change)
└─► Locations (rarely change)

Search Cache (30 sec TTL)
├─► Fast autocomplete
├─► High turnover
└─► Fresh enough for POS

Cross-Tab Sync
├─► Tab A: Product updated
├─► Tab A: localStorage.setItem('product_cache_invalidate')
├─► Tab B: Receives 'storage' event
└─► Tab B: Calls clearAllCaches()
```

## 🛡️ Error Handling Flow

```
API Request
    │
    ▼
apiClient.get/post()
    │
    ├─► Status 429 (Rate Limited)
    │   └─► Exponential Backoff
    │       ├─► Attempt 1: Wait 1s
    │       ├─► Attempt 2: Wait 2s
    │       └─► Attempt 3: Wait 4s
    │           └─► Max retries → Show error
    │
    ├─► Status 419 (CSRF Token Expired)
    │   └─► Show "Session expired" message
    │       └─► Prompt user to refresh
    │
    ├─► Status 500 (Server Error)
    │   └─► Show "Server error" toast
    │       └─► Log to console
    │
    └─► Network Error
        └─► Show "Network error" toast
            └─► Check internet connection
```

## 📱 Responsive Design Flow

```
Desktop View
├─► Full sidebar (categories, brands)
├─► Product grid (4 columns)
├─► Billing table (right sidebar)
└─► Desktop modals

Mobile View
├─► Bottom navigation
├─► Mobile product modal
├─► Mobile quantity modal
├─► Mobile payment modal
└─► Collapsible billing table
```

---

## 🎯 Module Dependencies

### Core Dependencies (No dependencies)
- `core/config.js`
- `core/constants.js`
- `core/utils.js`

### Layer 1 (Depends on Core)
- `cache/cacheManager.js`
- `api/apiClient.js`

### Layer 2 (Depends on Layer 1)
- All API services
- Event system

### Layer 3 (Depends on Layer 2)
- Business models
- Validators

### Layer 4 (Depends on Layer 3)
- UI components
- Feature modules

### Layer 5 (Orchestration)
- `main.js` (depends on all layers)

---

*This architecture ensures clean separation of concerns, testability, and maintainability while preserving all functionality from the original 11,607-line monolith.*
