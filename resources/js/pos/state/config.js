/**
 * POS Configuration
 * All configuration constants and settings
 */

export const config = {
    // API Endpoints
    api: {
        baseUrl: '/api',
        endpoints: {
            products: '/products',
            customers: '/customers',
            sales: '/sales',
            locations: '/locations',
            categories: '/categories',
            brands: '/brands',
            batches: '/batches',
            imei: '/imei',
            customerPrices: '/customer-prices',
            recentTransactions: '/recent-transactions'
        }
    },

    // Cache settings
    cache: {
        customer: {
            enabled: true,
            expiryMs: 5 * 60 * 1000 // 5 minutes
        },
        product: {
            enabled: true,
            expiryMs: 5 * 60 * 1000 // 5 minutes
        },
        static: {
            enabled: true,
            expiryMs: 10 * 60 * 1000 // 10 minutes
        },
        search: {
            enabled: true,
            expiryMs: 30 * 1000 // 30 seconds
        }
    },

    // Pagination
    pagination: {
        defaultPageSize: 50,
        productsPerPage: 100,
        maxPages: 1000
    },

    // Debounce timings
    debounce: {
        search: 300, // 300ms
        autocomplete: 100, // 100ms
        calculation: 50 // 50ms
    },

    // Validation
    validation: {
        price: {
            enabled: true,
            allowNegative: false,
            maxDecimals: 2
        },
        quantity: {
            allowNegative: false,
            maxDecimals: 2
        },
        discount: {
            maxPercentage: 100,
            allowNegative: false
        },
        imei: {
            length: 15,
            pattern: /^\d{15}$/
        }
    },

    // UI Settings
    ui: {
        loaderDelay: 300, // Show loader after 300ms
        toastDuration: 3000, // 3 seconds
        modalAnimation: true,
        confirmDeletion: true,
        autoSaveInterval: 60000 // 1 minute
    },

    // Hotkeys
    hotkeys: {
        enabled: true,
        shortcuts: {
            customer: 'F2',
            quickAdd: 'F3',
            payment: 'F4',
            clear: 'F5',
            finalize: 'F8',
            recent: 'F9'
        }
    },

    // Audio feedback
    audio: {
        enabled: true,
        volume: 0.5,
        sounds: {
            success: 'success',
            error: 'error',
            warning: 'warning',
            scan: 'scan'
        }
    },

    // Payment methods
    paymentMethods: [
        { value: 'cash', label: 'Cash' },
        { value: 'card', label: 'Card' },
        { value: 'cheque', label: 'Cheque' },
        { value: 'bank_transfer', label: 'Bank Transfer' },
        { value: 'credit', label: 'Credit' }
    ],

    // Shipping statuses
    shippingStatuses: [
        { value: 'pending', label: 'Pending' },
        { value: 'processing', label: 'Processing' },
        { value: 'shipped', label: 'Shipped' },
        { value: 'delivered', label: 'Delivered' },
        { value: 'cancelled', label: 'Cancelled' }
    ],

    // Discount types
    discountTypes: [
        { value: 'percentage', label: 'Percentage (%)' },
        { value: 'fixed', label: 'Fixed Amount' }
    ],

    // Number formatting
    formatting: {
        currency: {
            locale: 'en-US',
            decimals: 2,
            thousandsSeparator: ',',
            decimalSeparator: '.'
        },
        date: {
            format: 'YYYY-MM-DD',
            displayFormat: 'DD/MM/YYYY'
        }
    },

    // Feature flags
    features: {
        imeiTracking: true,
        batchManagement: true,
        customerPricing: true,
        salesRepRestrictions: true,
        shippingManagement: true,
        multiplePayments: false,
        returnManagement: false,
        loyaltyProgram: false
    },

    // Permissions (to be populated from backend)
    permissions: {
        canEditSale: false,
        canDeleteSale: false,
        canEditProduct: false,
        canDeleteProduct: false,
        canViewReports: false,
        canManageCustomers: false
    },

    // Sales Rep settings
    salesRep: {
        restrictLocations: true,
        restrictCustomers: true,
        filterByRoute: true,
        requireVehicle: true
    },

    // Error handling
    errors: {
        retryAttempts: 3,
        retryDelay: 1000, // 1 second
        showUserFriendlyMessages: true,
        logToConsole: true
    },

    // Development
    development: {
        debug: false,
        mockData: false,
        logStateChanges: false,
        logApiCalls: false
    }
};

/**
 * Update config with user permissions from backend
 * @param {Object} permissions - User permissions object
 */
export function updatePermissions(permissions) {
    config.permissions = {
        ...config.permissions,
        ...permissions
    };
}

/**
 * Update config with feature flags
 * @param {Object} features - Feature flags object
 */
export function updateFeatures(features) {
    config.features = {
        ...config.features,
        ...features
    };
}

/**
 * Get config value by path
 * @param {string} path - Dot-separated path (e.g., 'api.endpoints.products')
 * @returns {any} Config value
 */
export function getConfig(path) {
    return path.split('.').reduce((obj, key) => obj?.[key], config);
}

/**
 * Check if feature is enabled
 * @param {string} feature - Feature name
 * @returns {boolean} True if enabled
 */
export function isFeatureEnabled(feature) {
    return config.features[feature] === true;
}

/**
 * Check if user has permission
 * @param {string} permission - Permission name
 * @returns {boolean} True if has permission
 */
export function hasPermission(permission) {
    return config.permissions[permission] === true;
}

export default config;
