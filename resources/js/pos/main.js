/**
 * POS Main Controller
 * Central orchestration of all POS modules
 */

import { posState } from './state/index.js';
import config, { updatePermissions } from './state/config.js';
import { cacheManager } from './utils/cache.js';

// API
import { api } from './api/index.js';

// Modules
import { billingManager } from './modules/billing.js';
import { paymentManager } from './modules/payments.js';
import { imeiManager } from './modules/imei.js';
import { salesRepManager } from './modules/salesrep.js';
import { discountManager } from './modules/discounts.js';
import { productSearchManager } from './modules/productSearch.js';
import { locationManager } from './modules/locationManager.js';

// Components
import { modalManager } from './components/modals.js';
import { notificationManager } from './components/notifications.js';
import { loaderManager } from './components/loader.js';

class POSController {
    constructor() {
        this.initialized = false;
    }

    /**
     * Initialize POS system
     */
    async initialize() {
        if (this.initialized) {
            console.warn('POS already initialized');
            return;
        }

        console.log('🚀 Initializing Modular POS System...');

        try {
            // Initialize components
            loaderManager.initialize();
            loaderManager.show();

            // Initialize location manager first (needed by other modules)
            locationManager.initialize();

            // Initialize modules
            billingManager.initialize();
            paymentManager.initialize();
            imeiManager.initialize();
            salesRepManager.initialize();
            discountManager.initialize();
            productSearchManager.initialize();

            // Setup global event listeners
            this.setupGlobalEventListeners();

            // Setup hotkeys
            this.setupHotkeys();

            // Load initial data
            await this.loadInitialData();

            // Initialize Select2 dropdowns
            this.initializeSelect2();

            this.initialized = true;
            console.log('✅ Modular POS System Initialized Successfully');

            loaderManager.hide();
        } catch (error) {
            console.error('❌ POS Initialization Error:', error);
            notificationManager.error('Failed to initialize POS system', 'Initialization Error');
            loaderManager.hide();
        }
    }

    /**
     * Load initial data
     */
    async loadInitialData() {
        try {
            // Locations are loaded by locationManager.initialize()
            // Categories and brands are loaded by billingManager.initialize()

            console.log('✅ Initial data loaded');
        } catch (error) {
            console.error('Error loading initial data:', error);
        }
    }

    /**
     * Setup global event listeners
     */
    setupGlobalEventListeners() {
        // Location change
        const locationSelect = document.getElementById('location-select');
        if (locationSelect) {
            locationSelect.addEventListener('change', (e) => {
                posState.set('selectedLocationId', e.target.value);
                this.handleLocationChange(e.target.value);
            });
        }

        // Customer change
        const customerSelect = $('#customer-select');
        if (customerSelect.length) {
            customerSelect.on('change', (e) => {
                this.handleCustomerChange(e.target.value);
            });
        }

        // Quick add product button
        const quickAddBtn = document.getElementById('quick-add-btn');
        if (quickAddBtn) {
            quickAddBtn.addEventListener('click', () => modalManager.showQuickAddModal());
        }

        // Payment button
        const paymentBtn = document.getElementById('payment-btn');
        if (paymentBtn) {
            paymentBtn.addEventListener('click', () => this.handlePaymentClick());
        }

        // Clear button
        const clearBtn = document.getElementById('clear-btn');
        if (clearBtn) {
            clearBtn.addEventListener('click', () => this.handleClearClick());
        }

        // Custom events
        window.addEventListener('imei-selected', (e) => this.handleIMEISelected(e.detail));
        window.addEventListener('edit-imei', (e) => this.handleEditIMEI(e.detail));
    }

    /**
     * Setup keyboard shortcuts
     */
    setupHotkeys() {
        if (!config.hotkeys.enabled) return;

        document.addEventListener('keydown', (e) => {
            // Only handle hotkeys when not in input fields
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                return;
            }

            switch (e.key) {
                case 'F2':
                    e.preventDefault();
                    modalManager.showCustomerModal();
                    break;
                case 'F3':
                    e.preventDefault();
                    modalManager.showQuickAddModal();
                    break;
                case 'F4':
                    e.preventDefault();
                    this.handlePaymentClick();
                    break;
                case 'F5':
                    e.preventDefault();
                    this.handleClearClick();
                    break;
                case 'F8':
                    e.preventDefault();
                    paymentManager.processSale();
                    break;
                case 'F9':
                    e.preventDefault();
                    this.showRecentTransactions();
                    break;
            }
        });
    }

    /**
     * Initialize Select2 dropdowns
     */
    initializeSelect2() {
        if (typeof $ === 'undefined' || typeof $.fn.select2 === 'undefined') {
            console.warn('Select2 not available');
            return;
        }

        // Customer select
        $('#customer-select').select2({
            placeholder: 'Select Customer',
            allowClear: true,
            ajax: {
                url: '/sell/pos/search-customers',
                dataType: 'json',
                delay: 250,
                data: (params) => ({
                    term: params.term,
                    page: params.page || 1
                }),
                processResults: (data) => ({
                    results: data.customers.map(c => ({
                        id: c.id,
                        text: c.name
                    }))
                })
            }
        });

        // Product search
        $('#product-search').autocomplete({
            source: async (request, response) => {
                try {
                    const locationId = posState.get('selectedLocationId');
                    const results = await api.products.searchProducts(request.term, locationId);
                    response(results.map(p => ({
                        label: p.product_name,
                        value: p.id,
                        data: p
                    })));
                } catch (error) {
                    response([]);
                }
            },
            select: (event, ui) => {
                event.preventDefault();
                this.handleProductSelect(ui.item.data);
            },
            minLength: 2
        });
    }

    /**
     * Handle location change
     */
    async handleLocationChange(locationId) {
        console.log('Location changed to:', locationId);

        // Clear caches
        cacheManager.clear('products');
        cacheManager.clear('search');

        // Reload products if needed
        notificationManager.info('Location changed', 'Info');
    }

    /**
     * Handle customer change
     */
    async handleCustomerChange(customerId) {
        if (!customerId) {
            posState.set('currentCustomer', null);
            return;
        }

        try {
            const customer = await api.customers.getCustomer(customerId);
            posState.set('currentCustomer', customer);

            // Get customer type
            const customerType = await api.customers.getCustomerType(customerId);
            posState.set('customerTypeId', customerType.id);

            console.log('Customer selected:', customer.name);
        } catch (error) {
            console.error('Error loading customer:', error);
            notificationManager.error('Failed to load customer details', 'Error');
        }
    }

    /**
     * Handle product selection
     */
    async handleProductSelect(product) {
        try {
            loaderManager.show();

            const locationId = posState.get('selectedLocationId');

            // Get stock data
            const stockData = await api.products.getProductStock(product.id, locationId);

            if (stockData.total_stock <= 0) {
                notificationManager.warning('Product is out of stock', 'Warning');
                loaderManager.hide();
                return;
            }

            // Get customer pricing
            const customer = posState.get('currentCustomer');
            const customerTypeId = posState.get('customerTypeId');

            let price = product.default_sell_price;
            if (customer && customerTypeId) {
                const priceData = await api.customers.getCustomerPrice(
                    customer.id,
                    product.id,
                    customerTypeId
                );
                if (priceData) {
                    price = priceData.price;
                }
            }

            // Check if IMEI product
            if (product.enable_imei) {
                imeiManager.showIMEIModal(product.id, locationId);
                // Store product data for later use
                window.pendingIMEIProduct = { product, stockData, price };
            } else {
                // Add directly to billing
                await billingManager.addProduct({
                    product,
                    stockEntry: stockData,
                    price,
                    batchId: 'all',
                    batchQuantity: stockData.total_stock,
                    priceType: 'default',
                    saleQuantity: 1
                });
            }

            loaderManager.hide();
        } catch (error) {
            console.error('Error handling product select:', error);
            notificationManager.error('Failed to add product', 'Error');
            loaderManager.hide();
        }
    }

    /**
     * Handle IMEI selection
     */
    async handleIMEISelected(detail) {
        const { imeis } = detail;
        const pendingProduct = window.pendingIMEIProduct;

        if (!pendingProduct) {
            console.error('No pending IMEI product');
            return;
        }

        await billingManager.addProduct({
            ...pendingProduct,
            batchId: 'all',
            batchQuantity: pendingProduct.stockData.total_stock,
            priceType: 'default',
            saleQuantity: imeis.length,
            imeis
        });

        window.pendingIMEIProduct = null;
    }

    /**
     * Handle edit IMEI
     */
    handleEditIMEI(detail) {
        const { row } = detail;
        console.log('Edit IMEI for row:', row);
        // Implementation for editing IMEI
    }

    /**
     * Handle payment button click
     */
    handlePaymentClick() {
        const items = billingManager.getBillingData();

        if (items.length === 0) {
            notificationManager.warning('Please add items to the bill', 'Warning');
            return;
        }

        const customer = posState.get('currentCustomer');
        if (!customer) {
            notificationManager.warning('Please select a customer', 'Warning');
            return;
        }

        modalManager.showPaymentModal();
    }

    /**
     * Handle clear button click
     */
    handleClearClick() {
        if (confirm('Clear all items from the bill?')) {
            billingManager.clearBillingTable();
            posState.reset();
            notificationManager.success('Bill cleared', 'Success');
        }
    }

    /**
     * Show recent transactions
     */
    async showRecentTransactions() {
        try {
            const locationId = posState.get('selectedLocationId');
            const transactions = await api.sales.getRecentTransactions({ location_id: locationId });

            // Show in modal or table
            console.log('Recent transactions:', transactions);
        } catch (error) {
            console.error('Error loading recent transactions:', error);
            notificationManager.error('Failed to load recent transactions', 'Error');
        }
    }

    /**
     * Load sale for editing
     */
    async loadSaleForEdit(saleId) {
        try {
            loaderManager.show();

            const saleData = await api.sales.getSaleForEdit(saleId);

            // Set edit mode
            posState.update({
                isEditing: true,
                currentEditingSaleId: saleId
            });

            // Load customer
            posState.set('currentCustomer', saleData.customer);
            $('#customer-select').val(saleData.customer.id).trigger('change');

            // Load items
            for (const item of saleData.items) {
                await billingManager.addProduct({
                    product: item.product,
                    stockEntry: item.stock,
                    price: item.unit_price,
                    batchId: item.batch_id,
                    batchQuantity: item.quantity,
                    priceType: 'original',
                    saleQuantity: item.quantity,
                    imeis: item.imeis || [],
                    discountType: item.discount_type,
                    discountAmount: item.discount_amount
                });
            }

            loaderManager.hide();
            notificationManager.success('Sale loaded for editing', 'Success');
        } catch (error) {
            console.error('Error loading sale for edit:', error);
            notificationManager.error('Failed to load sale', 'Error');
            loaderManager.hide();
        }
    }
}

// Create singleton instance
export const posController = new POSController();

// Auto-initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => posController.initialize());
} else {
    posController.initialize();
}

// Export for global access
window.POSController = posController;

export default posController;
