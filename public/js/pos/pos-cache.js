/**
 * POS Cache Manager
 * Handles all caching logic for customers, products, locations, and search results
 */

class POSCache {
    constructor() {
        // Customer cache to avoid repeated AJAX calls
        this.customerCache = new Map();
        this.customerCacheExpiry = 5 * 60 * 1000; // 5 minutes

        // Static data cache (categories, brands, locations)
        this.staticDataCache = new Map();
        this.staticDataCacheExpiry = 10 * 60 * 1000; // 10 minutes for static data

        // Search results cache - 30 seconds for fast autocomplete while keeping data relatively fresh
        this.searchCache = new Map();
        this.searchCacheExpiry = 30 * 1000; // 30 seconds cache for performance

        // DOM element cache to avoid repeated getElementById calls
        this.domElementCache = {};

        // Simple cache for customer previous prices
        this.customerPriceCache = new Map();

        // Location cache
        this.cachedLocations = null;
        this.locationCacheExpiry = null;
        this.locationCacheDuration = 5 * 60 * 1000; // 5 minutes cache

        // Image failure cache to prevent repeated 404 errors
        this.failedImages = new Set();
        this.imageAttempts = new Map();

        this.init();
    }

    init() {
        // Listen for storage events from other tabs/windows
        window.addEventListener('storage', (e) => {
            if (e.key === 'product_cache_invalidate') {
                const timestamp = e.newValue;
                if (timestamp) {
                    console.log('🔄 Cache invalidation received from another tab/window');
                    this.clearAllCaches();
                }
            }
        });
    }

    /**
     * Clear all caches
     */
    clearAllCaches() {
        this.customerCache.clear();
        this.staticDataCache.clear();
        this.searchCache.clear();
        this.domElementCache = {};
        this.customerPriceCache.clear();
        this.cachedLocations = null;
        this.locationCacheExpiry = null;
        this.failedImages.clear();
        this.imageAttempts.clear();
        console.log('🗑️ All caches cleared due to data update');
    }

    /**
     * Check if customer data is cached and valid
     */
    getCachedCustomer(customerId) {
        const cacheKey = `customer_${customerId}`;
        const cached = this.customerCache.get(cacheKey);

        if (cached && (Date.now() - cached.timestamp < this.customerCacheExpiry)) {
            console.log('Using cached customer data for ID:', customerId);
            return cached.data;
        }

        return null;
    }

    /**
     * Cache customer data
     */
    setCachedCustomer(customerId, customerData) {
        const cacheKey = `customer_${customerId}`;
        this.customerCache.set(cacheKey, {
            data: customerData,
            timestamp: Date.now()
        });
        console.log('Cached customer data for ID:', customerId);
    }

    /**
     * Get cached static data (categories, brands, locations)
     */
    getCachedStaticData(key) {
        const cached = this.staticDataCache.get(key);

        if (cached && (Date.now() - cached.timestamp < this.staticDataCacheExpiry)) {
            console.log('Using cached static data for:', key);
            return cached.data;
        }

        return null;
    }

    /**
     * Set cached static data
     */
    setCachedStaticData(key, data) {
        this.staticDataCache.set(key, {
            data: data,
            timestamp: Date.now()
        });
        console.log('Cached static data for:', key);
    }

    /**
     * Get cached search results
     */
    getCachedSearch(key) {
        const cached = this.searchCache.get(key);

        if (cached && (Date.now() - cached.timestamp < this.searchCacheExpiry)) {
            return cached.data;
        }

        return null;
    }

    /**
     * Set cached search results
     */
    setCachedSearch(key, data) {
        this.searchCache.set(key, {
            data: data,
            timestamp: Date.now()
        });
    }

    /**
     * Get DOM element with caching to avoid repeated getElementById calls
     */
    getCachedElement(id) {
        if (!this.domElementCache[id]) {
            this.domElementCache[id] = document.getElementById(id);
        }
        return this.domElementCache[id];
    }

    /**
     * Clear DOM cache when elements might have changed
     */
    clearDOMCache() {
        this.domElementCache = {};
        console.log('DOM element cache cleared');
    }

    /**
     * Get customer previous price from cache
     */
    getCachedCustomerPrice(customerId, productId) {
        const key = `${customerId}_${productId}`;
        return this.customerPriceCache.get(key);
    }

    /**
     * Set customer previous price in cache
     */
    setCachedCustomerPrice(customerId, productId, price) {
        const key = `${customerId}_${productId}`;
        this.customerPriceCache.set(key, price);
    }

    /**
     * Check if image has failed to load
     */
    hasImageFailed(imageName) {
        return this.failedImages.has(imageName);
    }

    /**
     * Mark image as failed
     */
    markImageFailed(imageName) {
        this.failedImages.add(imageName);
    }

    /**
     * Get image attempt count
     */
    getImageAttempts(imageName) {
        return this.imageAttempts.get(imageName) || 0;
    }

    /**
     * Increment image attempt count
     */
    incrementImageAttempts(imageName) {
        const attempts = this.getImageAttempts(imageName) + 1;
        this.imageAttempts.set(imageName, attempts);
        return attempts;
    }

    /**
     * Notify other tabs about cache invalidation
     */
    notifyOtherTabsOfCacheInvalidation() {
        localStorage.setItem('product_cache_invalidate', Date.now());
        setTimeout(() => {
            localStorage.removeItem('product_cache_invalidate');
        }, 1000);
    }
}

// Create singleton instance
const posCache = new POSCache();

// Global functions for backward compatibility
window.refreshPOSCache = function() {
    posCache.clearAllCaches();

    // Reinitialize autocomplete to ensure fresh data
    if (typeof initAutocomplete === 'function') {
        console.log('🔄 Reinitializing autocomplete after cache clear');
        initAutocomplete();
    }

    if (window.selectedLocationId) {
        if (typeof fetchPaginatedProducts === 'function') {
            fetchPaginatedProducts(true);
        }
        toastr.info('Cache refreshed! Product data updated.', 'Cache Refresh');
    } else {
        toastr.warning('Please select a location first', 'No Location Selected');
    }
};

window.clearImageCache = function() {
    const count = posCache.failedImages.size;
    posCache.failedImages.clear();
    posCache.imageAttempts.clear();
    console.log(`🖼️ Cleared ${count} failed image entries from cache`);
    toastr.info(`Image cache cleared! (${count} entries removed)`, 'Cache Cleared');
};

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = POSCache;
} else {
    window.POSCache = POSCache;
    window.posCache = posCache;
}
