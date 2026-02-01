/**
 * Application Constants
 * Static values and configuration constants
 */

export const FALLBACK_IMAGE = '/assets/images/No Product Image Available.png';
export const PRIMARY_IMAGE_PATH = '/assets/images/';
export const SECONDARY_IMAGE_PATH = '/storage/products/';

export const CUSTOMER_TYPES = {
    WALK_IN: 'walk-in',
    WHOLESALER: 'wholesaler',
    RETAILER: 'retailer',
    DEALER: 'dealer'
};

export const SALE_STATUS = {
    PENDING: 'pending',
    COMPLETED: 'completed',
    SUSPENDED: 'suspended',
    DRAFT: 'draft',
    QUOTATION: 'quotation',
    SALE_ORDER: 'sale_order'
};

export const PAYMENT_METHODS = {
    CASH: 'cash',
    CARD: 'card',
    CHEQUE: 'cheque',
    CREDIT: 'credit',
    MULTIPLE: 'multiple'
};

export const PRICE_TYPES = {
    RETAIL: 'retail',
    WHOLESALE: 'wholesale',
    DEALER: 'dealer',
    MRP: 'mrp'
};

export const HOTKEYS = {
    F1: 'focusCustomer',
    F2: 'focusProduct',
    F3: 'openPaymentModal',
    F4: 'suspend',
    F5: 'draft',
    F6: 'quotation',
    F7: 'saleOrder',
    F8: 'openShipping',
    F9: 'clearAll'
};

export const ERROR_MESSAGES = {
    LOCATION_REQUIRED: 'Please select a location first',
    CUSTOMER_REQUIRED: 'Please select a customer',
    NO_PRODUCTS: 'Please add at least one product to the billing table',
    CREDIT_LIMIT_EXCEEDED: 'Credit limit exceeded for this customer',
    INSUFFICIENT_STOCK: 'Insufficient stock available',
    INVALID_QUANTITY: 'Please enter a valid quantity',
    PAYMENT_REQUIRED: 'Payment amount is required',
    SESSION_EXPIRED: 'Your session has expired. Please refresh the page.',
    NETWORK_ERROR: 'Network error. Please check your connection.'
};

export const SUCCESS_MESSAGES = {
    SALE_CREATED: 'Sale created successfully',
    SALE_UPDATED: 'Sale updated successfully',
    PRODUCT_ADDED: 'Product added to cart',
    CACHE_CLEARED: 'Cache cleared successfully'
};

export const VALIDATION_RULES = {
    MIN_PRODUCT_NAME_LENGTH: 2,
    MAX_PRODUCT_NAME_LENGTH: 255,
    MIN_QUANTITY: 0.01,
    MAX_QUANTITY: 999999,
    MIN_PRICE: 0,
    MAX_PRICE: 9999999999
};

// Request retry tracking
export const RETRY_CONFIG = {
    MAX_RETRIES: 3,
    BASE_DELAY: 1000,
    MAX_DELAY: 5000,
    BACKOFF_MULTIPLIER: 2
};

// Debounce delays
export const DEBOUNCE_DELAYS = {
    SEARCH: 300,
    FILTER: 500,
    QUANTITY_UPDATE: 200,
    PRICE_UPDATE: 300
};
