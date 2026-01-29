# POS System - Modular JavaScript Architecture

## Overview

The POS system has been refactored from a single 11,000+ line file into a modular, maintainable architecture. Each module has a specific responsibility and can be developed/tested independently.

## Module Structure

```
public/js/pos/
├── pos-cache.js          # Cache management for all data
├── pos-customer.js       # Customer management & pricing
├── pos-product.js        # Product display & filtering  
├── pos-location.js       # Location management
├── pos-salesrep.js       # Sales rep functionality
└── pos-controller.js     # Main controller (ties everything together)
```

## Modules Description

### 1. **pos-cache.js** - Cache Manager
Handles all caching to reduce API calls and improve performance.

**Features:**
- Customer data caching (5 min TTL)
- Static data caching (10 min TTL - categories, brands, locations)
- Search results caching (30 sec TTL)
- DOM element caching
- Customer price history caching
- Image failure tracking
- Cross-tab cache invalidation

**Global Functions:**
```javascript
window.refreshPOSCache()    // Clear all caches and reload
window.clearImageCache()    // Clear failed image cache
```

**Usage:**
```javascript
// Get cached customer
const customer = posCache.getCachedCustomer(customerId);

// Set cached data
posCache.setCachedCustomer(customerId, customerData);

// Clear all caches
posCache.clearAllCaches();
```

---

### 2. **pos-customer.js** - Customer Manager
Manages customer selection, pricing logic, and filtering.

**Features:**
- Get current customer with caching
- Customer type-based pricing (retail/wholesale)
- Price calculation with batch support
- Update billing row prices
- Customer filtering by route (for sales reps)
- Previous purchase price tracking

**Key Methods:**
```javascript
customerManager.getCurrentCustomer()           // Get selected customer
customerManager.getCustomerTypePrice()         // Get price based on customer type
customerManager.updateAllBillingRowsPricing()  // Update all billing rows
customerManager.filterCustomersByRoute()       // Filter for sales rep
```

**Usage:**
```javascript
// Get current customer
const customer = posCustomer.getCurrentCustomer();
console.log(customer.type); // 'retail' or 'wholesaler'

// Get price for customer
const priceInfo = posCustomer.getCustomerTypePrice(batch, product, customer.type);
console.log(priceInfo.price); // Final price
```

---

### 3. **pos-product.js** - Product Manager
Handles product display, filtering, searching, and infinite scroll.

**Features:**
- Paginated product fetching with retry logic
- Product display with stock filtering
- Category/Subcategory/Brand filtering
- Product search
- Infinite scroll loading
- Image error handling
- Empty state & error state handling

**Key Methods:**
```javascript
productManager.fetchProducts(locationId, reset)      // Fetch products
productManager.displayProducts(products, append)     // Display products
productManager.filterByCategory(categoryId)          // Filter by category
productManager.filterByBrand(brandId)                // Filter by brand
productManager.searchProducts(searchTerm)            // Search products
productManager.showAllProducts(locationId)           // Clear filters
```

**Usage:**
```javascript
// Fetch products for location
await posProduct.fetchProducts(locationId, true);

// Filter by category
posProduct.filterByCategory(5, locationId);

// Search products
posProduct.searchProducts('laptop');
```

---

### 4. **pos-location.js** - Location Manager
Manages location selection and filtering.

**Features:**
- Fetch all locations with caching
- Populate location dropdowns
- Handle location changes
- Sync mobile & desktop dropdowns
- Auto-select location (for sales rep/edit mode)
- Filter locations by sales rep vehicle
- Last location persistence

**Key Methods:**
```javascript
locationManager.fetchLocations(forceRefresh)         // Fetch locations
locationManager.handleLocationChange(locationId)     // Handle change
locationManager.autoSelectLocation(locationId)       // Auto-select
locationManager.filterLocationsBySalesRep(vehicleId) // Filter for sales rep
```

**Usage:**
```javascript
// Fetch locations
await posLocation.fetchLocations();

// Handle location change
posLocation.handleLocationChange(locationId, productManager);

// Auto-select for sales rep
posLocation.autoSelectLocation(vehicleId);
```

---

### 5. **pos-salesrep.js** - Sales Rep Manager
Handles sales rep functionality including vehicle/route selection and customer filtering.

**Features:**
- Check if user is sales rep
- Show vehicle/route selection modal
- Save/restore selections
- Filter customers by route cities
- Filter locations by vehicle
- Display sales rep info (desktop & mobile)

**Key Methods:**
```javascript
salesRepManager.checkStatus(callback)              // Check if sales rep
salesRepManager.showSelectionModal()               // Show selection modal
salesRepManager.saveSelection(selection)           // Save selection
salesRepManager.restrictLocationAccess()           // Restrict locations
```

**Usage:**
```javascript
// Check sales rep status
posSalesRep.checkStatus((isSalesRep) => {
    if (isSalesRep) {
        console.log('User is a sales rep');
    }
});

// Get saved selection
const selection = posSalesRep.getSavedSelection();
```

---

### 6. **pos-controller.js** - Main Controller
Orchestrates all modules and provides unified interface.

**Features:**
- Initialize all modules in correct order
- Coordinate module interactions
- Handle customer changes
- Setup product search
- Check edit mode
- Expose global functions for backward compatibility
- Provide system state for debugging

**Key Methods:**
```javascript
posController.init()                    // Initialize POS system
posController.refresh()                 // Refresh everything
posController.resetForNewSale()         // Reset for new sale
posController.getState()                // Get current state (debug)
```

**Usage:**
```javascript
// Initialize (happens automatically on DOM ready)
const posController = new POSController();
await posController.init();

// Refresh POS
posController.refresh();

// Check state
console.log(posController.getState());
```

---

## Integration Guide

### Step 1: Include Modules in Blade Template

Add this line in your main POS blade file (before pos_ajax.blade.php):

```blade
@include('sell.partials.pos-modules')
```

### Step 2: Remove Duplicate Code from pos_ajax.blade.php

The following code blocks can now be removed from pos_ajax.blade.php as they're handled by modules:

1. ✅ All cache-related functions (lines ~40-130)
2. ✅ Customer management functions (lines ~390-1030)
3. ✅ Location fetch/display functions (lines ~2573-2670)
4. ✅ Product fetch/display functions (lines ~2917-3320)
5. ✅ Sales rep functions (lines ~1028-2215)

### Step 3: Update Function Calls

The modules expose global functions for backward compatibility, so existing code should work without changes:

```javascript
// These still work
fetchPaginatedProducts(true);
displayProducts(products, false);
getCurrentCustomer();
filterProductsByCategory(categoryId);
```

---

## Global Variables (Backward Compatibility)

These variables are exposed globally for existing code:

```javascript
window.posController       // Main controller instance
window.posCache           // Cache manager instance
window.posCustomer        // Customer manager instance
window.posProduct         // Product manager instance
window.posLocation        // Location manager instance
window.posSalesRep        // Sales rep manager instance

// State variables
window.selectedLocationId
window.allProducts
window.isLoadingProducts
window.hasMoreProducts
window.isEditing
window.isSalesRep
```

---

## Benefits of New Architecture

### 1. **Maintainability**
- Each module has a single responsibility
- Easy to locate and fix bugs
- Clear separation of concerns

### 2. **Performance**
- Efficient caching reduces API calls
- Lazy loading of products
- Optimized DOM operations

### 3. **Testability**
- Modules can be tested independently
- Clear interfaces between modules
- Easy to mock dependencies

### 4. **Scalability**
- Easy to add new features
- Can extract modules to separate files
- Supports future microservices architecture

### 5. **Developer Experience**
- Better code organization
- Easier onboarding for new developers
- Comprehensive documentation

---

## Debugging

### Check POS State
```javascript
console.log(posController.getState());
```

Output:
```javascript
{
    initialized: true,
    isEditing: false,
    selectedLocationId: 5,
    productsLoaded: 150,
    isSalesRep: true,
    hasSelection: true
}
```

### Check Module Status
```javascript
console.log('Cache size:', posCache.customerCache.size);
console.log('Products loaded:', posProduct.allProducts.length);
console.log('Selected location:', posLocation.selectedLocationId);
console.log('Is sales rep:', posSalesRep.isSalesRep);
```

### Force Refresh
```javascript
posController.refresh();           // Refresh everything
window.refreshPOSCache();          // Clear caches and reload
window.clearImageCache();          // Clear image cache
```

---

## Migration Checklist

- [x] Create modular structure
- [x] Extract cache management
- [x] Extract customer management  
- [x] Extract product management
- [x] Extract location management
- [x] Extract sales rep functionality
- [x] Create main controller
- [x] Create module loader
- [ ] Test all functionality
- [ ] Remove duplicate code from pos_ajax.blade.php
- [ ] Deploy to production

---

## API Endpoints Used

### Products
- `GET /products/stocks` - Get products with stock
- `GET /products/search` - Search products

### Customers
- `GET /customer-get-by-id/{id}` - Get customer details
- `GET /sell/pos/customer-type/{id}` - Get customer type
- `POST /customers/filter-by-cities` - Filter by cities

### Locations
- `GET /location-get-all` - Get all locations
- `GET /sell/pos/get-location/{id}` - Get location details

### Sales Rep
- `GET /sales-rep/my-assignments` - Get assignments
- `GET /sales-rep/my-routes` - Get routes

---

## Future Enhancements

1. **TypeScript Migration** - Add type safety
2. **Unit Tests** - Add comprehensive test coverage
3. **API Module** - Centralize all API calls (already started in resources/js/pos/api/)
4. **State Management** - Use Vuex or Redux pattern
5. **Web Workers** - Offload heavy computations
6. **Service Worker** - Add offline support
7. **Real-time Updates** - WebSocket integration

---

## Support

For issues or questions about the modular architecture:
1. Check browser console for error messages
2. Use `posController.getState()` for debugging
3. Review module documentation above
4. Contact: development team

---

## License

Proprietary - Marazin POS System
