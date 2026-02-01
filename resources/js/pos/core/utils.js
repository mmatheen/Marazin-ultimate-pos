/**
 * Core Utility Functions
 * Extracted from lines 301-700, 10401-10570 of monolithic pos_ajax.blade.php
 * All helper functions for safe operations, formatting, and error handling
 */

import { FALLBACK_IMAGE, PRIMARY_IMAGE_PATH, SECONDARY_IMAGE_PATH } from './constants.js';
import { POSState } from './config.js';

/**
 * Debounce function to limit API calls
 */
export function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Safely parse numeric values to prevent Infinity/-Infinity errors
 */
export function safeParseFloat(value, defaultValue = 0) {
    const parsed = parseFloat(value);
    return (isFinite(parsed) && !isNaN(parsed)) ? parsed : defaultValue;
}

/**
 * Safely calculate percentage to prevent division by zero
 */
export function safePercentage(numerator, denominator, defaultValue = 0) {
    if (!denominator || denominator === 0 || !isFinite(denominator)) {
        return defaultValue;
    }
    const result = (numerator / denominator) * 100;
    return isFinite(result) ? result : defaultValue;
}

/**
 * Format amount with thousand separators
 */
export function formatAmountWithSeparators(amount) {
    return parseFloat(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

/**
 * Parse formatted amount back to number
 */
export function parseFormattedAmount(formattedAmount) {
    if (typeof formattedAmount === 'number') return formattedAmount;
    if (!formattedAmount) return 0;

    // Remove commas and parse
    return safeParseFloat(formattedAmount.toString().replace(/,/g, ''));
}

/**
 * Format currency display
 */
export function formatCurrency(amount) {
    return `Rs. ${formatAmountWithSeparators(amount)}`;
}

/**
 * Batch DOM updates to minimize reflows/repaints
 */
export function batchDOMUpdates(updates) {
    // Use DocumentFragment for batch operations
    const fragment = document.createDocumentFragment();

    // Execute all updates in a single frame
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

/**
 * Get safe image URL with fallback
 */
export function getSafeImageUrl(product) {
    if (!product || !product.product_image || product.product_image.trim() === '') {
        return FALLBACK_IMAGE;
    }

    const imageName = product.product_image.trim();

    if (POSState.failedImages.has(imageName)) {
        return FALLBACK_IMAGE;
    }

    if (imageName.startsWith('http') || imageName.startsWith('/')) {
        return imageName;
    }

    return `${PRIMARY_IMAGE_PATH}${imageName}`;
}

/**
 * Create image element with error handling
 */
export function createSafeImage(product, styles = '', className = '', title = '') {
    const img = document.createElement('img');

    img.src = getSafeImageUrl(product);
    if (styles) img.style.cssText = styles;
    if (className) img.className = className;
    if (title) img.title = title;
    img.alt = product?.product_name || 'Product';
    img.loading = 'lazy';

    img.onerror = function() {
        if (this.src === FALLBACK_IMAGE) return;

        const originalImage = product?.product_image?.trim();
        if (!originalImage) {
            this.src = FALLBACK_IMAGE;
            return;
        }

        // Try storage path once if not already tried
        if (!this.src.includes(SECONDARY_IMAGE_PATH)) {
            this.src = `${SECONDARY_IMAGE_PATH}${originalImage}`;
        } else {
            POSState.failedImages.add(originalImage);
            this.src = FALLBACK_IMAGE;
        }
    };

    return img;
}

/**
 * Check image health (diagnostic)
 */
export function checkImageHealth() {
    const images = document.querySelectorAll('img[src*="assets/images"], img[src*="storage/products"]');
    const missingCount = Array.from(images).filter(img =>
        !img.src.includes('No Product Image Available.png') &&
        img.naturalWidth === 0 &&
        img.complete
    ).length;

    console.log(`🖼️ Checking ${images.length} product images...`);
    console.log(`📊 Image Health: ${missingCount}/${images.length} missing`);
    if (missingCount === 0) console.log('🎉 All images loading correctly!');
}

/**
 * Refresh product images
 */
export function refreshProductImages() {
    const images = document.querySelectorAll('.product-card img');
    images.forEach(img => {
        if (img.dataset.productImage) {
            const product = { product_image: img.dataset.productImage };
            img.src = getSafeImageUrl(product);
        }
    });
    if (images.length > 0) console.log(`🔄 Refreshed ${images.length} images`);
}

/**
 * Clean up modal backdrops and body styles
 */
export function cleanupModalBackdrop() {
    // Remove all modal backdrops
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => backdrop.remove());

    // Reset body styles
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';

    console.log('Modal backdrop cleanup completed');
}

/**
 * Prevent double-click on buttons
 */
export function preventDoubleClick(button, callback) {
    if (button.dataset.isProcessing === "true") return;
    button.dataset.isProcessing = "true";
    button.disabled = true;

    try {
        callback();
    } catch (error) {
        console.error('Button callback error:', error);
        enableButton(button);
    }
}

/**
 * Enable button after processing
 */
export function enableButton(button) {
    button.disabled = false;
    button.dataset.isProcessing = "false";

    // Restore button text based on button type
    const $button = $(button);
    if ($button.attr('id') === 'cashButton') {
        $button.html('<i class="fa fa-money"></i> Cash');
    } else if ($button.attr('id') === 'cardButton') {
        $button.html('<i class="fa fa-credit-card"></i> Card');
    } else if ($button.attr('id') === 'creditButton') {
        $button.html('<i class="fa fa-credit-card"></i> Credit');
    } else {
        $button.html($button.data('original-text') || 'Submit');
    }
}

/**
 * Safe AJAX call with button protection
 */
export function safeAjaxCall(button, options) {
    preventDoubleClick(button, () => {
        const originalComplete = options.complete;
        options.complete = function(...args) {
            enableButton(button);
            if (originalComplete) originalComplete.apply(this, args);
        };
        $.ajax(options);
    });
}

/**
 * Get CSRF token
 */
export function getCSRFToken() {
    return $('meta[name="csrf-token"]').attr('content');
}

/**
 * Play sound notification
 */
export function playSound(type = 'success') {
    try {
        const sound = document.querySelector(`.${type}Sound`);
        if (sound) sound.play();
    } catch (error) {
        console.warn('Could not play sound:', error);
    }
}

/**
 * Show toast notification
 */
export function showToast(message, type = 'info', title = '') {
    if (typeof toastr !== 'undefined') {
        toastr[type](message, title);

        // Play sound for success/error
        if (type === 'success') playSound('success');
        if (type === 'error') playSound('error');
        if (type === 'warning') playSound('warning');
    }
}

/**
 * Generate unique ID
 */
export function generateUniqueId(prefix = 'id') {
    return `${prefix}_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
}

/**
 * Deep clone object
 */
export function deepClone(obj) {
    return JSON.parse(JSON.stringify(obj));
}

/**
 * Check if value is empty
 */
export function isEmpty(value) {
    return value === null || value === undefined || value === '' ||
           (Array.isArray(value) && value.length === 0) ||
           (typeof value === 'object' && Object.keys(value).length === 0);
}

/**
 * Throttle function execution
 */
export function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

/**
 * Get query parameter from URL
 */
export function getQueryParam(param) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(param);
}

/**
 * Set query parameter in URL
 */
export function setQueryParam(param, value) {
    const url = new URL(window.location);
    url.searchParams.set(param, value);
    window.history.pushState({}, '', url);
}

/**
 * Scroll to element smoothly
 */
export function scrollToElement(element, offset = 0) {
    if (!element) return;

    const elementPosition = element.getBoundingClientRect().top + window.pageYOffset;
    const offsetPosition = elementPosition - offset;

    window.scrollTo({
        top: offsetPosition,
        behavior: 'smooth'
    });
}

/**
 * Copy text to clipboard
 */
export async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
        showToast('Copied to clipboard', 'success');
        return true;
    } catch (error) {
        console.error('Failed to copy:', error);
        showToast('Failed to copy to clipboard', 'error');
        return false;
    }
}

/**
 * Format date for display
 */
export function formatDate(date, format = 'YYYY-MM-DD') {
    if (typeof moment !== 'undefined') {
        return moment(date).format(format);
    }
    return new Date(date).toLocaleDateString();
}

/**
 * Validate email format
 */
export function isValidEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

/**
 * Validate phone format
 */
export function isValidPhone(phone) {
    const regex = /^[0-9]{10,15}$/;
    return regex.test(phone.replace(/[\s\-\(\)]/g, ''));
}

/**
 * Truncate text with ellipsis
 */
export function truncateText(text, maxLength = 50) {
    if (!text || text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
}

/**
 * Wait for element to exist in DOM
 */
export function waitForElement(selector, timeout = 5000) {
    return new Promise((resolve, reject) => {
        const element = document.querySelector(selector);
        if (element) {
            resolve(element);
            return;
        }

        const observer = new MutationObserver(() => {
            const element = document.querySelector(selector);
            if (element) {
                observer.disconnect();
                resolve(element);
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        setTimeout(() => {
            observer.disconnect();
            reject(new Error(`Element ${selector} not found within ${timeout}ms`));
        }, timeout);
    });
}

// Expose to window for debugging
if (typeof window !== 'undefined') {
    window.POSUtils = {
        safeParseFloat,
        formatAmountWithSeparators,
        showToast,
        cleanupModalBackdrop
    };
}
