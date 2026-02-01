/**
 * Cache Manager
 * Central caching system with cross-tab synchronization
 * Extracted from lines 351-500 of monolithic pos_ajax.blade.php
 */

import { POSConfig, POSState } from '../core/config.js';

class CacheManager {
    constructor() {
        this.caches = {
            customer: new Map(),
            staticData: new Map(),
            search: new Map(),
            customerPrice: new Map()
        };

        this.setupCrossTabSync();
    }

    /**
     * Setup cross-tab cache synchronization
     */
    setupCrossTabSync() {
        window.addEventListener('storage', (e) => {
            if (e.key === 'product_cache_invalidate') {
                this.clearAll();
                // Refresh current product display
                if (POSState.selectedLocationId) {
                    console.log('🔄 Refreshing products due to cache invalidation');
                    window.dispatchEvent(new CustomEvent('refreshProducts'));
                }
            }
        });
    }

    /**
     * Notify other tabs about cache invalidation
     */
    notifyOtherTabs() {
        try {
            localStorage.setItem('product_cache_invalidate', Date.now().toString());
            setTimeout(() => {
                localStorage.removeItem('product_cache_invalidate');
            }, 1000);
        } catch (error) {
            console.warn('Could not notify other tabs:', error);
        }
    }

    /**
     * Get customer from cache
     */
    getCachedCustomer(customerId) {
        const cacheKey = `customer_${customerId}`;
        const cached = this.caches.customer.get(cacheKey);

        if (cached && (Date.now() - cached.timestamp < POSConfig.cache.customerExpiry)) {
            console.log('✅ Customer cache hit:', customerId);
            return cached.data;
        }

        return null;
    }

    /**
     * Set customer in cache
     */
    setCachedCustomer(customerId, customerData) {
        const cacheKey = `customer_${customerId}`;
        this.caches.customer.set(cacheKey, {
            data: customerData,
            timestamp: Date.now()
        });
        console.log('💾 Cached customer data for ID:', customerId);
    }

    /**
     * Get static data from cache (categories, brands, locations)
     */
    getCachedStaticData(key) {
        const cached = this.caches.staticData.get(key);

        if (cached && (Date.now() - cached.timestamp < POSConfig.cache.staticDataExpiry)) {
            console.log('✅ Static data cache hit:', key);
            return cached.data;
        }

        return null;
    }

    /**
     * Set static data in cache
     */
    setCachedStaticData(key, data) {
        this.caches.staticData.set(key, {
            data: data,
            timestamp: Date.now()
        });
        console.log('💾 Cached static data for:', key);
    }

    /**
     * Get search results from cache
     */
    getCachedSearch(searchTerm) {
        const cached = this.caches.search.get(searchTerm);

        if (cached && (Date.now() - cached.timestamp < POSConfig.cache.searchExpiry)) {
            console.log('✅ Search cache hit:', searchTerm);
            return cached.data;
        }

        return null;
    }

    /**
     * Set search results in cache
     */
    setCachedSearch(searchTerm, results) {
        this.caches.search.set(searchTerm, {
            data: results,
            timestamp: Date.now()
        });
    }

    /**
     * Get customer price from cache
     */
    getCachedCustomerPrice(customerId, productId) {
        const cacheKey = `${customerId}_${productId}`;
        return this.caches.customerPrice.get(cacheKey);
    }

    /**
     * Set customer price in cache
     */
    setCachedCustomerPrice(customerId, productId, price) {
        const cacheKey = `${customerId}_${productId}`;
        this.caches.customerPrice.set(cacheKey, price);
    }

    /**
     * Clear customer price cache
     */
    clearCustomerPriceCache() {
        this.caches.customerPrice.clear();
        console.log('🗑️ Customer price cache cleared');
    }

    /**
     * Clear all caches
     */
    clearAll() {
        this.caches.customer.clear();
        this.caches.staticData.clear();
        this.caches.search.clear();
        this.caches.customerPrice.clear();

        // Clear location cache
        POSState.cachedLocations = null;
        POSState.locationCacheExpiry = null;

        // Clear image failure cache
        POSState.failedImages.clear();
        POSState.imageAttempts.clear();

        // Clear DOM cache
        if (window.domElementCache) {
            window.domElementCache = {};
        }

        console.log('🗑️ All caches cleared');
    }

    /**
     * Clear specific cache type
     */
    clear(type) {
        if (this.caches[type]) {
            this.caches[type].clear();
            console.log(`🗑️ ${type} cache cleared`);
        }
    }

    /**
     * Get cache statistics
     */
    getStats() {
        return {
            customer: this.caches.customer.size,
            staticData: this.caches.staticData.size,
            search: this.caches.search.size,
            customerPrice: this.caches.customerPrice.size,
            failedImages: POSState.failedImages.size
        };
    }
}

// Create singleton instance
const cacheManager = new CacheManager();

// Global cache management functions
window.refreshPOSCache = function() {
    cacheManager.clearAll();

    // Reinitialize autocomplete to ensure fresh data
    if (typeof window.initAutocomplete === 'function') {
        try {
            $("#productSearchInput").autocomplete('destroy');
            window.initAutocomplete();
            console.log('🔄 Autocomplete reinitialized');
        } catch (e) {
            console.warn('Could not reinitialize autocomplete:', e.message);
        }
    }

    if (POSState.selectedLocationId) {
        console.log('🔄 Manual cache refresh initiated');
        window.dispatchEvent(new CustomEvent('refreshProducts'));
        if (typeof toastr !== 'undefined') {
            toastr.info('Cache refreshed! Product data updated.', 'Cache Refresh');
        }
    } else {
        console.log('ℹ️ No location selected, only cache cleared');
        if (typeof toastr !== 'undefined') {
            toastr.info('Cache cleared. Select a location to refresh products.', 'Cache Cleared');
        }
    }
};

window.refreshLocationCache = function() {
    console.log('🔄 Refreshing location cache...');
    POSState.cachedLocations = null;
    POSState.locationCacheExpiry = null;
    window.dispatchEvent(new CustomEvent('refreshLocations', { detail: { forceRefresh: true } }));
    if (typeof toastr !== 'undefined') {
        toastr.info('Location cache refreshed!', 'Cache Refresh');
    }
};

window.clearImageCache = function() {
    const count = POSState.failedImages.size;
    POSState.failedImages.clear();
    POSState.imageAttempts.clear();
    console.log(`🖼️ Cleared ${count} failed image entries from cache`);
    if (typeof toastr !== 'undefined') {
        toastr.info(`Image cache cleared! (${count} entries removed)`, 'Cache Cleared');
    }
};

window.getCacheStats = function() {
    const stats = cacheManager.getStats();
    console.table(stats);
    return stats;
};

export default cacheManager;
