/**
 * POS CACHE MODULE — Phase 3
 * All in-memory cache Maps and their getter/setter helpers.
 * Depends on: nothing (pure data structures).
 * Must load AFTER pos-utils.js, BEFORE pos_ajax.blade.php.
 *
 * Variables extracted from pos_ajax.blade.php:
 *   customerCache, staticDataCache, searchCache,
 *   domElementCache, customerPriceCache
 *
 * Functions extracted from pos_ajax.blade.php:
 *   getCachedCustomer, setCachedCustomer,
 *   getCachedStaticData, setCachedStaticData,
 *   getCachedElement, clearDOMCache,
 *   notifyOtherTabsOfCacheInvalidation
 *
 * NOTE: clearAllCaches(), window.refreshPOSCache, the storage listener
 * are still in pos_ajax.blade.php — they reference stockData, cachedLocations,
 * failedImages which are not yet extracted. They will move in a later phase.
 */

// ---- Cache Maps ----
let customerCache        = new Map();
let customerCacheExpiry  = 5 * 60 * 1000;   // 5 minutes

let staticDataCache      = new Map();
let staticDataCacheExpiry = 10 * 60 * 1000; // 10 minutes

let searchCache          = new Map();
let searchCacheExpiry    = 30 * 1000;        // 30 seconds

let domElementCache      = {};

const customerPriceCache = new Map();

// ---- Customer Cache ----

/**
 * Check if customer data is cached and valid
 */
function getCachedCustomer(customerId) {
    const cacheKey = `customer_${customerId}`;
    const cached   = customerCache.get(cacheKey);

    if (cached && (Date.now() - cached.timestamp < customerCacheExpiry)) {
        return cached.data;
    }

    return null;
}

/**
 * Cache customer data
 */
function setCachedCustomer(customerId, customerData) {
    const cacheKey = `customer_${customerId}`;
    customerCache.set(cacheKey, {
        data:      customerData,
        timestamp: Date.now()
    });
}

// ---- Static Data Cache (categories, brands, locations) ----

/**
 * Get cached static data
 */
function getCachedStaticData(key) {
    const cached = staticDataCache.get(key);

    if (cached && (Date.now() - cached.timestamp < staticDataCacheExpiry)) {
        return cached.data;
    }

    return null;
}

/**
 * Set cached static data
 */
function setCachedStaticData(key, data) {
    staticDataCache.set(key, {
        data:      data,
        timestamp: Date.now()
    });
}

// ---- DOM Element Cache ----

/**
 * Get DOM element with caching to avoid repeated getElementById calls
 */
function getCachedElement(id) {
    if (!domElementCache[id]) {
        domElementCache[id] = document.getElementById(id);
    }
    return domElementCache[id];
}

/**
 * Clear DOM cache when elements might have changed
 */
function clearDOMCache() {
    domElementCache = {};
}

// ---- Cross-tab Cache Invalidation ----

function notifyOtherTabsOfCacheInvalidation() {
    localStorage.setItem('product_cache_invalidate', Date.now());
    setTimeout(() => {
        localStorage.removeItem('product_cache_invalidate');
    }, 1000);
}

// ---- Expose all as globals (required for jQuery-based pos_ajax.blade.php) ----
window.customerCache                    = customerCache;
window.customerCacheExpiry              = customerCacheExpiry;
window.staticDataCache                  = staticDataCache;
window.staticDataCacheExpiry            = staticDataCacheExpiry;
window.searchCache                      = searchCache;
window.searchCacheExpiry                = searchCacheExpiry;
window.domElementCache                  = domElementCache;
window.customerPriceCache               = customerPriceCache;

window.getCachedCustomer                = getCachedCustomer;
window.setCachedCustomer                = setCachedCustomer;
window.getCachedStaticData              = getCachedStaticData;
window.setCachedStaticData              = setCachedStaticData;
window.getCachedElement                 = getCachedElement;
window.clearDOMCache                    = clearDOMCache;
window.notifyOtherTabsOfCacheInvalidation = notifyOtherTabsOfCacheInvalidation;
