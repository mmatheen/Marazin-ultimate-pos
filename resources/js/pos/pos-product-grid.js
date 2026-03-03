/**
 * POS PRODUCT GRID MODULE — Phase 8
 * Product filtering by category, sub-category, and brand.
 * Show-all-products reset helper.
 *
 * Extracted from resources/views/sell/pos_ajax.blade.php
 * Functions: filterProductsByCategory, filterProductsBySubCategory,
 *            filterProductsByBrand, showAllProducts
 *
 * Architecture note
 * -----------------
 * These functions are thin orchestration wrappers. The heavy lifting
 * (AJAX, DOM rendering, pagination) stays inside the pos_ajax closure
 * via window.fetchFilteredProducts / window.fetchPaginatedProducts.
 *
 * All shared mutable state is bridged through window.* so both the
 * closure code and this module read/write through the same channel:
 *   window.selectedLocationId   — selected location (read-only here)
 *   window.currentFilter        — { type, id } read by setupLazyLoad
 *   window.setCurrentFilter(f)  — relay that updates the closure var
 *   window.showLoader()         — show product-grid spinner
 *   window.fetchFilteredProducts(type, id, reset) — closure AJAX function
 *   window.fetchPaginatedProducts(reset)           — closure AJAX function
 *
 * Must load BEFORE pos_ajax.blade.php (listed earlier in @vite chain).
 *
 * Public API:
 *   window.Pos.ProductGrid.filterProductsByCategory,
 *   window.Pos.ProductGrid.filterProductsBySubCategory,
 *   window.Pos.ProductGrid.filterProductsByBrand,
 *   window.Pos.ProductGrid.showAllProducts
 */

'use strict';

// POS namespace for product grid helpers
window.Pos = window.Pos || {};
window.Pos.ProductGrid = window.Pos.ProductGrid || {};

/* ------------------------------------------------------------------ */
/* filterProductsByCategory                                             */
/* ------------------------------------------------------------------ */
/**
 * Filter the product grid by a main category.
 * @param {number} categoryId
 */
function filterProductsByCategory(categoryId) {
    if (!window.selectedLocationId) {
        if (typeof toastr !== 'undefined') {
            toastr.error('Please select a location first', 'Location Required');
        }
        return;
    }

    // Update closure's currentFilter via relay so setupLazyLoad stays in sync
    if (typeof window.setCurrentFilter === 'function') {
        window.setCurrentFilter({ type: 'category', id: categoryId });
    }

    // Show spinner immediately for instant feedback
    if (typeof window.showLoader === 'function') window.showLoader();

    // Delegate to closure's AJAX function (handles reset + pagination internally)
    if (typeof window.fetchFilteredProducts === 'function') {
        window.fetchFilteredProducts('category', categoryId, true);
    }
}

/* ------------------------------------------------------------------ */
/* filterProductsBySubCategory                                          */
/* ------------------------------------------------------------------ */
/**
 * Filter the product grid by a sub-category.
 * @param {number} subCategoryId
 */
function filterProductsBySubCategory(subCategoryId) {
    if (!window.selectedLocationId) {
        if (typeof toastr !== 'undefined') {
            toastr.error('Please select a location first', 'Location Required');
        }
        return;
    }

    if (typeof window.setCurrentFilter === 'function') {
        window.setCurrentFilter({ type: 'subcategory', id: subCategoryId });
    }

    if (typeof window.showLoader === 'function') window.showLoader();

    if (typeof window.fetchFilteredProducts === 'function') {
        window.fetchFilteredProducts('subcategory', subCategoryId, true);
    }
}

/* ------------------------------------------------------------------ */
/* filterProductsByBrand                                                */
/* ------------------------------------------------------------------ */
/**
 * Filter the product grid by brand.
 * @param {number} brandId
 */
function filterProductsByBrand(brandId) {
    if (!window.selectedLocationId) {
        if (typeof toastr !== 'undefined') {
            toastr.error('Please select a location first', 'Location Required');
        }
        return;
    }

    if (typeof window.setCurrentFilter === 'function') {
        window.setCurrentFilter({ type: 'brand', id: brandId });
    }

    if (typeof window.showLoader === 'function') window.showLoader();

    if (typeof window.fetchFilteredProducts === 'function') {
        window.fetchFilteredProducts('brand', brandId, true);
    }
}

/* ------------------------------------------------------------------ */
/* showAllProducts                                                      */
/* ------------------------------------------------------------------ */
/**
 * Clear any active filter and reload all products for the current location.
 * Used by the "All Products" button and error-state retry buttons
 * (onclick="showAllProducts()" in rendered HTML).
 */
function showAllProducts() {

    // Clear active filter so setupLazyLoad's scroll handler uses fetchPaginatedProducts
    if (typeof window.setCurrentFilter === 'function') {
        window.setCurrentFilter({ type: null, id: null });
    }

    // Clear grid DOM immediately (fetchPaginatedProducts(reset=true) also clears it,
    // but doing it here gives instant visual feedback)
    const posProductEl = document.getElementById('posProduct');
    if (posProductEl) posProductEl.innerHTML = '';

    // Delegate full reload to the closure's paginated-fetch function
    if (typeof window.fetchPaginatedProducts === 'function') {
        window.fetchPaginatedProducts(true);
    }
}

/* ------------------------------------------------------------------ */
/* Expose via namespace (+ minimal global shims for inline handlers)  */
/* ------------------------------------------------------------------ */
window.Pos.ProductGrid.filterProductsByCategory    = filterProductsByCategory;
window.Pos.ProductGrid.filterProductsBySubCategory = filterProductsBySubCategory;
window.Pos.ProductGrid.filterProductsByBrand       = filterProductsByBrand;
window.Pos.ProductGrid.showAllProducts             = showAllProducts;

// Global shims for existing onclick="..." handlers and legacy calls
window.filterProductsByCategory    = filterProductsByCategory;
window.filterProductsBySubCategory = filterProductsBySubCategory;
window.filterProductsByBrand       = filterProductsByBrand;
window.showAllProducts             = showAllProducts;
