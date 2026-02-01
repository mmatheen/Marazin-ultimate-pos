/**
 * Main POS Application Orchestrator
 * Central coordination point for the modular POS system
 * Replaces monolithic 11,607-line pos_ajax.blade.php
 */

import { POSConfig, POSState, shippingData, autocompleteState } from './core/config.js';
import { showToast, cleanupModalBackdrop } from './core/utils.js';
import cacheManager from './cache/cacheManager.js';
import apiClient from './api/apiClient.js';
import productService from './api/productService.js';

class POSApplication {
    constructor() {
        this.initialized = false;
        this.domElements = {};
    }

    /**
     * Initialize the POS application
     */
    async init() {
        if (this.initialized) {
            console.warn('POS Application already initialized');
            return;
        }

        console.log('🚀 Initializing Modular POS System...');

        try {
            // 1. Setup global error handlers
            this.setupErrorHandlers();

            // 2. Setup CSRF token for jQuery
            this.setupCSRFToken();

            // 3. Cache DOM elements
            this.cacheDOMElements();

            // 4. Initialize components in sequence
            await this.initializeComponents();

            // 5. Setup event listeners
            this.setupEventListeners();

            // 6. Check for edit mode
            this.checkEditMode();

            // 7. Initialize autocomplete
            if (typeof window.initAutocomplete === 'function') {
                window.initAutocomplete();
            }

            this.initialized = true;
            console.log('✅ POS Application initialized successfully');

            // Expose to window for debugging
            window.POSApp = this;

        } catch (error) {
            console.error('❌ Failed to initialize POS Application:', error);
            showToast('Failed to initialize POS system. Please refresh the page.', 'error');
        }
    }

    /**
     * Setup global error handlers
     */
    setupErrorHandlers() {
        window.addEventListener('error', (e) => {
            // Handle appendChild errors
            if (e.message && e.message.includes('appendChild')) {
                console.error('appendChild error detected:', e);
                e.preventDefault();
                return false;
            }

            // Handle Infinity/-Infinity parsing errors
            if (e.message && (e.message.includes('Infinity') || e.message.includes('cannot be parsed'))) {
                console.error('Infinity parsing error detected:', e);
                e.preventDefault();
                return false;
            }
        });

        console.log('✅ Global error handlers registered');
    }

    /**
     * Setup CSRF token for jQuery AJAX
     */
    setupCSRFToken() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Debug function
        window.checkCSRFToken = function() {
            const token = $('meta[name="csrf-token"]').attr('content');
            console.log('CSRF Token:', token);
            console.log('Token length:', token ? token.length : 'No token');
            return token;
        };

        console.log('✅ CSRF token configured');
    }

    /**
     * Cache critical DOM elements
     */
    cacheDOMElements() {
        this.domElements = {
            posProduct: document.getElementById('posProduct'),
            billingBody: document.getElementById('billing-body'),
            discountInput: document.getElementById('discount'),
            finalValue: document.getElementById('total'),
            categoryBtn: document.getElementById('category-btn'),
            allProductsBtn: document.getElementById('allProductsBtn'),
            subcategoryBackBtn: document.getElementById('subcategoryBackBtn'),
            locationSelect: document.getElementById('locationSelect'),
            locationSelectDesktop: document.getElementById('locationSelectDesktop'),
            customerSelect: document.getElementById('customer-id'),
            productSearchInput: document.getElementById('productSearchInput')
        };

        // Log initialization status
        Object.entries(this.domElements).forEach(([key, element]) => {
            console.log(`DOM Element ${key}:`, element ? '✓ Found' : '✗ NOT FOUND');
        });

        // Expose for backward compatibility
        window.posProduct = this.domElements.posProduct;
        window.billingBody = this.domElements.billingBody;
    }

    /**
     * Initialize all components in proper sequence
     */
    async initializeComponents() {
        console.log('📦 Loading components...');

        // Load locations first (critical for subsequent operations)
        await this.loadLocations();

        // Check sales rep status and setup restrictions
        await this.checkSalesRepStatus();

        // Load categories and brands
        this.loadCategories();
        this.loadBrands();

        // Setup image health check
        setTimeout(() => {
            if (typeof window.checkImageHealth === 'function') {
                window.checkImageHealth();
            }
            if (typeof window.refreshProductImages === 'function') {
                window.refreshProductImages();
            }
        }, 3000);
    }

    /**
     * Load locations dropdown
     */
    async loadLocations() {
        return new Promise((resolve) => {
            if (typeof window.fetchAllLocations === 'function') {
                window.fetchAllLocations(false, () => {
                    console.log('✅ Locations loaded');
                    resolve();
                });
            } else {
                console.warn('fetchAllLocations function not found');
                resolve();
            }
        });
    }

    /**
     * Check sales rep status and apply restrictions
     */
    async checkSalesRepStatus() {
        return new Promise((resolve) => {
            if (typeof window.checkSalesRepStatus === 'function') {
                window.checkSalesRepStatus((isUserSalesRep) => {
                    POSState.isSalesRep = isUserSalesRep;
                    console.log('✅ Sales rep status checked:', isUserSalesRep);
                    resolve();
                });
            } else {
                resolve();
            }
        });
    }

    /**
     * Load categories
     */
    loadCategories() {
        if (typeof window.fetchCategories === 'function') {
            try {
                window.fetchCategories();
            } catch (error) {
                console.error('Failed to load categories:', error);
            }
        }
    }

    /**
     * Load brands
     */
    loadBrands() {
        if (typeof window.fetchBrands === 'function') {
            try {
                window.fetchBrands();
            } catch (error) {
                console.error('Failed to load brands:', error);
            }
        }
    }

    /**
     * Setup global event listeners
     */
    setupEventListeners() {
        // Location change handlers
        if (this.domElements.locationSelect) {
            $(this.domElements.locationSelect).on('change', window.handleLocationChange);
        }
        if (this.domElements.locationSelectDesktop) {
            $(this.domElements.locationSelectDesktop).on('change', window.handleLocationChange);
        }

        // Customer change handler
        if (this.domElements.customerSelect) {
            $(this.domElements.customerSelect).on('change', () => {
                // Clear price cache when customer changes
                cacheManager.clearCustomerPriceCache();

                // Update pricing for existing products in billing table
                if (typeof window.updateAllBillingRowsPricing === 'function') {
                    const customer = window.getCurrentCustomer();
                    if (customer && !POSState.isEditing) {
                        window.updateAllBillingRowsPricing(customer.customer_type);
                    }
                }
            });
        }

        // Global discount handlers
        const globalDiscountInput = document.getElementById('global-discount');
        const globalDiscountTypeInput = document.getElementById('discount-type');

        if (globalDiscountInput) {
            $(globalDiscountInput).on('input', () => {
                if (typeof window.updateTotals === 'function') {
                    window.updateTotals();
                }
            });
        }

        if (globalDiscountTypeInput) {
            $(globalDiscountTypeInput).on('change', () => {
                if (typeof window.updateTotals === 'function') {
                    window.updateTotals();
                }
            });
        }

        // Auto-focus product search input
        setTimeout(() => {
            if (this.domElements.productSearchInput) {
                this.domElements.productSearchInput.focus();
            }
        }, 500);

        // Visibility change handler (refocus on tab switch)
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && this.domElements.productSearchInput) {
                setTimeout(() => {
                    this.domElements.productSearchInput.focus();
                }, 100);
            }
        });

        console.log('✅ Event listeners registered');
    }

    /**
     * Check if page is in edit mode
     */
    checkEditMode() {
        const pathSegments = window.location.pathname.split('/');
        const saleId = pathSegments[pathSegments.length - 1];

        if (!isNaN(saleId) && saleId !== 'pos' && saleId !== 'list-sale') {
            console.log('📝 Edit mode detected for sale:', saleId);
            POSState.isEditing = true;
            POSState.currentEditingSaleId = saleId;

            if (typeof window.fetchEditSale === 'function') {
                window.fetchEditSale(saleId);
            }
        }
    }

    /**
     * Refresh all data (manual cache clear)
     */
    refresh() {
        cacheManager.clearAll();

        if (POSState.selectedLocationId) {
            window.dispatchEvent(new CustomEvent('refreshProducts'));
            showToast('POS data refreshed successfully', 'success');
        } else {
            showToast('Cache cleared. Select a location to load products.', 'info');
        }
    }

    /**
     * Get current application state (for debugging)
     */
    getState() {
        return {
            initialized: this.initialized,
            selectedLocationId: POSState.selectedLocationId,
            isEditing: POSState.isEditing,
            isSalesRep: POSState.isSalesRep,
            productsLoaded: POSState.allProducts.length,
            cacheStats: cacheManager.getStats()
        };
    }
}

// Initialize application when DOM is ready
document.addEventListener('DOMContentLoaded', async () => {
    const app = new POSApplication();
    await app.init();
});

// Expose for debugging
window.POSApplication = POSApplication;

export default POSApplication;
