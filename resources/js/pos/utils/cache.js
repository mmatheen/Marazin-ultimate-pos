/**
 * POS Cache Management
 * Handles all caching operations with expiry
 */

export class CacheManager {
    constructor() {
        this.caches = new Map();
    }

    /**
     * Create a new cache store
     * @param {string} name - Cache name
     * @param {number} expiryMs - Expiry time in milliseconds
     */
    createCache(name, expiryMs = 5 * 60 * 1000) {
        this.caches.set(name, {
            data: new Map(),
            expiry: expiryMs
        });
    }

    /**
     * Set cache value
     * @param {string} cacheName - Cache name
     * @param {string} key - Cache key
     * @param {any} value - Value to cache
     */
    set(cacheName, key, value) {
        const cache = this.caches.get(cacheName);
        if (!cache) {
            return;
        }

        cache.data.set(key, {
            value: value,
            timestamp: Date.now()
        });
    }

    /**
     * Get cache value
     * @param {string} cacheName - Cache name
     * @param {string} key - Cache key
     * @returns {any} Cached value or null if expired/not found
     */
    get(cacheName, key) {
        const cache = this.caches.get(cacheName);
        if (!cache) {
            return null;
        }

        const item = cache.data.get(key);
        if (!item) {
            return null;
        }

        // Check if expired
        const age = Date.now() - item.timestamp;
        if (age > cache.expiry) {
            cache.data.delete(key);
            return null;
        }

        return item.value;
    }

    /**
     * Check if cache has key
     * @param {string} cacheName - Cache name
     * @param {string} key - Cache key
     * @returns {boolean} True if key exists and not expired
     */
    has(cacheName, key) {
        return this.get(cacheName, key) !== null;
    }

    /**
     * Delete cache entry
     * @param {string} cacheName - Cache name
     * @param {string} key - Cache key
     */
    delete(cacheName, key) {
        const cache = this.caches.get(cacheName);
        if (cache) {
            cache.data.delete(key);
        }
    }

    /**
     * Clear entire cache
     * @param {string} cacheName - Cache name
     */
    clear(cacheName) {
        const cache = this.caches.get(cacheName);
        if (cache) {
            cache.data.clear();
        }
    }

    /**
     * Clear all caches
     */
    clearAll() {
        this.caches.forEach(cache => cache.data.clear());
    }

    /**
     * Get cache statistics
     * @param {string} cacheName - Cache name
     * @returns {Object} Cache statistics
     */
    getStats(cacheName) {
        const cache = this.caches.get(cacheName);
        if (!cache) {
            return null;
        }

        return {
            size: cache.data.size,
            expiryMs: cache.expiry
        };
    }
}

// Create singleton instance
export const cacheManager = new CacheManager();

// Initialize default caches
cacheManager.createCache('customers', 5 * 60 * 1000); // 5 minutes
cacheManager.createCache('products', 5 * 60 * 1000); // 5 minutes
cacheManager.createCache('static', 10 * 60 * 1000); // 10 minutes
cacheManager.createCache('search', 30 * 1000); // 30 seconds
cacheManager.createCache('dom', Infinity); // Never expire
