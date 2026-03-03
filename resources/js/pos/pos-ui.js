/**
 * POS UI MODULE — Phase 4
 * UI helpers: image handling, safe fetch, modal cleanup, DOM batching.
 * Depends on: pos-utils.js (safeParseFloat), pos-cache.js (getCachedElement)
 * Must load AFTER pos-utils.js and pos-cache.js, BEFORE pos_ajax.blade.php.
 *
 * Extracted from pos_ajax.blade.php:
 *   failedImages, imageAttempts,
 *   getSafeImageUrl, createSafeImage, checkImageHealth, refreshProductImages,
 *   safeFetchJson, batchDOMUpdates, cleanupModalBackdrop
 *
 * NOTE: showLoader, hideLoader, showLoaderSmall, initDOMElements are still in
 * pos_ajax.blade.php — they depend on the local `posProduct` var which will
 * be extracted in a later phase.
 *
 * Public API:
 *   window.Pos.UI.getSafeImageUrl,
 *   window.Pos.UI.createSafeImage,
 *   window.Pos.UI.checkImageHealth,
 *   window.Pos.UI.refreshProductImages,
 *   window.Pos.UI.safeFetchJson,
 *   window.Pos.UI.batchDOMUpdates,
 *   window.cleanupModalBackdrop   (global helper)
 */

// POS namespace for UI helpers
window.Pos = window.Pos || {};
window.Pos.UI = window.Pos.UI || {};

// ---- Image failure tracking ----
let failedImages  = new Set();
let imageAttempts = new Map(); // tracks retry attempts per image (was undeclared in original — bug fix)

// ---- Image Helpers ----

/**
 * Get safe image URL with fallback
 */
function getSafeImageUrl(product) {
    const fallbackImage = '/assets/images/No Product Image Available.png';

    if (!product || !product.product_image || product.product_image.trim() === '') {
        return fallbackImage;
    }

    const imageName = product.product_image.trim();

    if (failedImages.has(imageName)) {
        return fallbackImage;
    }

    if (imageName.startsWith('http') || imageName.startsWith('/')) {
        return imageName;
    }

    return `/assets/images/${imageName}`;
}

/**
 * Create image element with error handling and fallback retry
 */
function createSafeImage(product, styles = '', className = '', title = '') {
    const fallbackImage = '/assets/images/No Product Image Available.png';
    const img = document.createElement('img');

    img.src = getSafeImageUrl(product);
    if (styles) img.style.cssText = styles;
    if (className) img.className = className;
    if (title) img.title = title;
    img.alt = product?.product_name || 'Product';
    img.loading = 'lazy';

    img.onerror = function() {
        if (this.src === fallbackImage) return;

        const originalImage = product?.product_image?.trim();
        if (!originalImage) {
            this.src = fallbackImage;
            return;
        }

        // Try storage path once if not already tried
        if (!this.src.includes('/storage/products/')) {
            this.src = `/storage/products/${originalImage}`;
        } else {
            failedImages.add(originalImage);
            this.src = fallbackImage;
        }
    };

    return img;
}

/**
 * Check image health (diagnostic)
 */
function checkImageHealth() {
    const images = document.querySelectorAll('img[src*="assets/images"], img[src*="storage/products"]');
    const missingCount = Array.from(images).filter(img =>
        !img.src.includes('No Product Image Available.png') &&
        img.naturalWidth === 0 &&
        img.complete
    ).length;
}

/**
 * Refresh all product card images
 */
function refreshProductImages() {
    const images = document.querySelectorAll('.product-card img');
    images.forEach(img => {
        if (img.dataset.productImage) {
            const product = { product_image: img.dataset.productImage };
            img.src = getSafeImageUrl(product);
        }
    });
}

// ---- Safe Fetch ----

/**
 * Fetch wrapper with default JSON headers, rate-limit handling and non-JSON detection
 */
function safeFetchJson(url, options = {}) {
    const defaultOptions = {
        cache: 'no-store',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        ...options
    };

    return fetch(url, defaultOptions).then(res => {
        if (!res.ok) {
            if (res.status === 429) {
                const retryAfter = parseInt(res.headers.get('Retry-After') || '2', 10) * 1000;
                console.warn(`Rate limited on ${url}. Retry after ${retryAfter}ms`);
                return Promise.reject({
                    status: 429,
                    retryAfter,
                    message: `Rate limited. Please wait ${Math.ceil(retryAfter / 1000)} seconds.`
                });
            }
            return res.text().then(text => Promise.reject({
                status: res.status,
                text,
                message: `HTTP ${res.status}: ${res.statusText}`
            }));
        }

        const contentType = res.headers.get('content-type') || '';
        if (contentType.indexOf('application/json') === -1) {
            return res.text().then(text => {
                console.warn('Non-JSON response received:', text.substring(0, 200));
                return Promise.reject({
                    text,
                    message: 'Server returned non-JSON response. Please check server configuration.'
                });
            });
        }

        return res.json();
    });
}

// ---- DOM Batching ----

/**
 * Batch DOM updates to minimise reflows/repaints
 */
function batchDOMUpdates(updates) {
    requestAnimationFrame(() => {
        updates.forEach(update => {
            try {
                update();
            } catch (error) {
                console.error('Batch DOM update error:', error);
            }
        });
    });
}

// ---- Modal Backdrop Cleanup ----

window.cleanupModalBackdrop = function() {
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => backdrop.remove());
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
};

// ---- Expose all as namespaced helpers + legacy globals ----
window.Pos.UI.failedImages          = failedImages;
window.Pos.UI.imageAttempts         = imageAttempts;
window.Pos.UI.getSafeImageUrl       = getSafeImageUrl;
window.Pos.UI.createSafeImage       = createSafeImage;
window.Pos.UI.checkImageHealth      = checkImageHealth;
window.Pos.UI.refreshProductImages  = refreshProductImages;
window.Pos.UI.safeFetchJson         = safeFetchJson;
window.Pos.UI.batchDOMUpdates       = batchDOMUpdates;

// Backwards-compatible globals used by older modules
window.failedImages         = failedImages;
window.imageAttempts        = imageAttempts;
window.getSafeImageUrl      = getSafeImageUrl;
window.createSafeImage      = createSafeImage;
window.checkImageHealth     = checkImageHealth;
window.refreshProductImages = refreshProductImages;
window.safeFetchJson        = safeFetchJson;
window.batchDOMUpdates      = batchDOMUpdates;
