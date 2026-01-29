/**
 * Main POS Controller
 * Initializes and coordinates all POS modules
 */

class POSController {
    constructor() {
        this.cache = null;
        this.customerManager = null;
        this.productManager = null;
        this.locationManager = null;
        this.salesRepManager = null;
        this.isEditing = false;
        this.currentEditingSaleId = null;
        this.initialized = false;
    }

    /**
     * Initialize POS system
     */
    async init() {
        if (this.initialized) {
            console.warn('POS already initialized');
            return;
        }

        console.log('🚀 Initializing POS System...');

        try {
            // Initialize cache
            this.cache = new POSCache();
            console.log('✅ Cache manager initialized');

            // Initialize customer manager
            this.customerManager = new POSCustomerManager(this.cache);
            console.log('✅ Customer manager initialized');

            // Initialize product manager
            this.productManager = new POSProductManager(this.cache);
            console.log('✅ Product manager initialized');

            // Initialize location manager
            this.locationManager = new POSLocationManager(this.cache);
            console.log('✅ Location manager initialized');

            // Initialize sales rep manager
            this.salesRepManager = new POSSalesRepManager(
                this.cache,
                this.customerManager,
                this.locationManager
            );
            console.log('✅ Sales rep manager initialized');

            // Make managers available globally for backward compatibility
            window.posCache = this.cache;
            window.posCustomer = this.customerManager;
            window.posProduct = this.productManager;
            window.posLocation = this.locationManager;
            window.posSalesRep = this.salesRepManager;

            // Fetch locations first (critical for sales rep and edit mode)
            await this.locationManager.fetchLocations(false, () => {
                console.log('✅ Locations loaded, checking sales rep status...');

                // Check sales rep status
                this.salesRepManager.checkStatus((isSalesRep) => {
                    if (isSalesRep) {
                        console.log('👤 User is a sales rep');
                    }

                    // Check for edit mode
                    this.checkEditMode();
                });
            });

            // Setup location change listeners
            this.locationManager.setupListeners(this.productManager);

            // Setup infinite scroll for products
            const locationChangeHandler = () => {
                if (this.locationManager.selectedLocationId) {
                    this.productManager.setupInfiniteScroll(this.locationManager.selectedLocationId);
                }
            };
            window.addEventListener('locationChanged', locationChangeHandler);

            // Listen for customer changes to update pricing
            $('#customer-id').on('change', () => {
                this.handleCustomerChange();
            });

            // Setup product search
            this.setupProductSearch();

            // Setup global functions for backward compatibility
            this.setupGlobalFunctions();

            this.initialized = true;
            console.log('✅ POS System fully initialized');

        } catch (error) {
            console.error('❌ Error initializing POS:', error);
            if (typeof toastr !== 'undefined') {
                toastr.error('Failed to initialize POS system', 'Error');
            }
        }
    }

    /**
     * Handle customer change
     */
    handleCustomerChange() {
        // Clear price cache when customer changes
        this.cache.customerPriceCache.clear();

        // Get new customer type
        const customer = this.customerManager.getCurrentCustomer();
        console.log('Customer changed to:', customer);

        // Update pricing for existing billing rows
        const billingBody = document.getElementById('billing-body');
        if (billingBody) {
            const existingRows = billingBody.querySelectorAll('tr');
            if (existingRows.length > 0 && !this.isEditing) {
                this.customerManager.updateAllBillingRowsPricing(customer.type);
            }
        }

        // Update floating balance display (if function exists)
        if (typeof updateFloatingBalance === 'function') {
            updateFloatingBalance();
        }
    }

    /**
     * Setup product search
     */
    setupProductSearch() {
        const searchInput = document.getElementById('productSearchInput');
        if (!searchInput) return;

        let searchTimeout;

        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            const searchTerm = e.target.value;

            searchTimeout = setTimeout(() => {
                this.productManager.searchProducts(searchTerm);
            }, 300); // Debounce 300ms
        });

        console.log('✅ Product search setup complete');
    }

    /**
     * Check for edit mode
     */
    checkEditMode() {
        const pathSegments = window.location.pathname.split('/');
        const saleId = pathSegments[pathSegments.length - 1];

        if (!isNaN(saleId) && saleId !== 'pos' && saleId !== 'list-sale') {
            console.log('📝 Edit mode detected, sale ID:', saleId);
            this.isEditing = true;
            this.currentEditingSaleId = saleId;
            window.isEditing = true;
            window.currentEditingSaleId = saleId;

            // Fetch edit sale data
            if (typeof fetchEditSale === 'function') {
                fetchEditSale(saleId);
            }
        } else {
            console.log('📄 New sale mode');
            this.isEditing = false;
            window.isEditing = false;
        }
    }

    /**
     * Setup global functions for backward compatibility
     */
    setupGlobalFunctions() {
        // Expose key functions globally
        window.fetchPaginatedProducts = (reset = false) => {
            const locationId = this.locationManager.selectedLocationId;
            if (locationId) {
                this.productManager.fetchProducts(locationId, reset);
            }
        };

        window.displayProducts = (products, append = false) => {
            this.productManager.displayProducts(products, append);
        };

        window.filterProductsByCategory = (categoryId) => {
            const locationId = this.locationManager.selectedLocationId;
            this.productManager.filterByCategory(categoryId, locationId);
        };

        window.filterProductsBySubCategory = (subCategoryId) => {
            const locationId = this.locationManager.selectedLocationId;
            this.productManager.filterBySubCategory(subCategoryId, locationId);
        };

        window.filterProductsByBrand = (brandId) => {
            const locationId = this.locationManager.selectedLocationId;
            this.productManager.filterByBrand(brandId, locationId);
        };

        window.showAllProducts = () => {
            const locationId = this.locationManager.selectedLocationId;
            this.productManager.showAllProducts(locationId);
        };

        window.getCurrentCustomer = () => {
            return this.customerManager.getCurrentCustomer();
        };

        window.getCustomerTypePrice = (batch, product, customerType) => {
            return this.customerManager.getCustomerTypePrice(batch, product, customerType);
        };

        // Expose state variables
        window.selectedLocationId = this.locationManager.selectedLocationId;
        window.allProducts = this.productManager.allProducts;
        window.isLoadingProducts = this.productManager.isLoading;
        window.hasMoreProducts = this.productManager.hasMoreProducts;

        // Make controller available globally
        window.posController = this;

        console.log('✅ Global functions setup complete');
    }

    /**
     * Refresh everything
     */
    refresh() {
        console.log('🔄 Refreshing POS...');

        // Clear all caches
        this.cache.clearAllCaches();

        // Reload products if location is selected
        if (this.locationManager.selectedLocationId) {
            this.productManager.fetchProducts(this.locationManager.selectedLocationId, true);
        }

        // Reload locations
        this.locationManager.fetchLocations(true);

        if (typeof toastr !== 'undefined') {
            toastr.info('POS refreshed successfully', 'Refresh');
        }
    }

    /**
     * Reset for new sale
     */
    resetForNewSale() {
        console.log('🔄 Resetting POS for new sale...');

        this.isEditing = false;
        this.currentEditingSaleId = null;
        window.isEditing = false;
        window.currentEditingSaleId = null;

        // Clear billing table
        const billingBody = document.getElementById('billing-body');
        if (billingBody) {
            billingBody.innerHTML = '';
        }

        // Reset customer selection
        const customerSelect = $('#customer-id');
        if (customerSelect.length) {
            customerSelect.val('').trigger('change');
        }

        // Update totals
        if (typeof updateTotals === 'function') {
            updateTotals();
        }

        console.log('✅ POS reset complete');
    }

    /**
     * Get current state (for debugging)
     */
    getState() {
        return {
            initialized: this.initialized,
            isEditing: this.isEditing,
            currentEditingSaleId: this.currentEditingSaleId,
            selectedLocationId: this.locationManager?.selectedLocationId,
            productsLoaded: this.productManager?.allProducts.length || 0,
            isSalesRep: this.salesRepManager?.isSalesRep || false,
            hasSelection: this.salesRepManager?.hasValidSelection() || false
        };
    }
}

// Initialize POS when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    console.log('📄 DOM loaded, initializing POS...');

    // Create and initialize POS controller
    const posController = new POSController();
    posController.init();

    // Make it globally available
    window.posController = posController;

    // Log state for debugging
    setTimeout(() => {
        console.log('📊 POS State:', posController.getState());
    }, 2000);
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = POSController;
} else {
    window.POSController = POSController;
}
