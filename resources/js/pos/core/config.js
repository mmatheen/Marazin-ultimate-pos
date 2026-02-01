/**
 * Core Configuration
 * Global flags, permissions, and application state
 * Extracted from lines 1-200 of monolithic pos_ajax.blade.php
 */

export const POSConfig = {
    // User permissions (injected from backend)
    permissions: {
        canEditSale: window.userPermissions?.canEditSale || false,
        canDeleteSale: window.userPermissions?.canDeleteSale || false,
        canEditProduct: window.userPermissions?.canEditProduct || false,
        canDeleteProduct: window.userPermissions?.canDeleteProduct || false
    },

    // Cache expiry durations (milliseconds)
    cache: {
        customerExpiry: 5 * 60 * 1000,        // 5 minutes
        staticDataExpiry: 10 * 60 * 1000,     // 10 minutes
        searchExpiry: 30 * 1000,              // 30 seconds
        locationExpiry: 5 * 60 * 1000,        // 5 minutes
        salesDataExpiry: 30 * 1000            // 30 seconds
    },

    // Pagination settings
    pagination: {
        perPage: 24,
        throttleDelay: 200
    },

    // API retry configuration
    api: {
        maxRetries: 3,
        baseRetryDelay: 1000,
        timeout: 30000
    },

    // Feature flags
    features: {
        priceValidationEnabled: window.priceValidationEnabled || 0,
        flexiblePricing: window.flexiblePricing || false,
        imeiTracking: true,
        batchTracking: true,
        salesRepWorkflow: true
    },

    // Scanner configuration (MP6300Y compatible)
    scanner: {
        minInputLength: 5,
        maxInputTime: 100,
        preventDuplicates: true,
        autoSubmit: true,
        prefixes: [''],
        suffixes: ['Enter', '\n']
    },

    // Sales rep filter cooldown
    salesRep: {
        filterCooldown: 2000,           // 2 seconds
        customerResetCooldown: 5000     // 5 seconds
    }
};

// Global application state
export const POSState = {
    // Location state
    selectedLocationId: null,
    cachedLocations: null,
    locationCacheExpiry: null,

    // Product state
    currentProductsPage: 1,
    hasMoreProducts: true,
    isLoadingProducts: false,
    allProducts: [],
    stockData: [],

    // Edit mode state
    isEditing: false,
    currentEditingSaleId: null,
    isEditingFinalizedSale: false,

    // Sales rep state
    isSalesRep: false,
    salesRepCustomersFiltered: false,
    salesRepCustomersLoaded: false,
    filteringInProgress: false,
    isCurrentlyFiltering: false,
    lastCustomerFilterCall: 0,

    // Filter state
    currentFilter: {
        type: null,
        id: null
    },

    // Image failure tracking
    failedImages: new Set(),
    imageAttempts: new Map(),

    // Active modal tracking
    activeModalProductId: null,
    currentImeiProduct: null,
    currentImeiStockEntry: null,
    selectedImeisInBilling: [],

    // Recent transactions
    sales: [],
    lastSalesDataFetch: 0,
    isLoadingTableData: false,

    // Misc flags
    isErrorShown: false,
    preventAutoSelection: false,
    salesRepCustomerResetInProgress: false,
    lastCustomerResetTime: null,
    customerDataLoading: false,
    fetchingSalesData: false,
    recentTransactionsInitialized: false
};

// Shipping data global state
export const shippingData = {
    shipping_details: '',
    shipping_address: '',
    shipping_charges: 0,
    shipping_status: 'pending',
    delivered_to: '',
    delivery_person: ''
};

// Autocomplete state
export const autocompleteState = {
    lastSearchTerm: '',
    isProcessing: false,
    pendingRequest: null,
    resultCount: 0,
    exactMatchFound: false,
    autoAddTriggered: false
};

// Expose critical state to window for debugging
if (typeof window !== 'undefined') {
    window.POSState = POSState;
    window.POSConfig = POSConfig;
}
