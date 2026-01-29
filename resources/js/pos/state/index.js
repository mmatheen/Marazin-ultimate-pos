/**
 * POS State Management
 * Centralized state management for the POS system
 */

class POSState {
    constructor() {
        this.state = {
            // Location
            selectedLocationId: null,

            // Customer
            currentCustomer: null,
            customerTypeId: null,

            // Products
            currentProductsPage: 1,
            hasMoreProducts: true,
            isLoadingProducts: false,
            allProducts: [],
            stockData: {},

            // Billing
            billingItems: [],
            subtotal: 0,
            totalDiscount: 0,
            taxAmount: 0,
            finalTotal: 0,

            // Edit mode
            isEditing: false,
            currentEditingSaleId: null,
            isEditingFinalizedSale: false,

            // Sales Rep
            isSalesRep: false,
            salesRepId: null,
            salesRepCustomersFiltered: false,
            salesRepVehicleId: null,

            // UI flags
            showLoader: false,
            activeModal: null,

            // Shipping
            shippingData: {
                shipping_details: '',
                shipping_address: '',
                shipping_charges: 0,
                shipping_status: 'pending',
                delivered_to: '',
                delivery_person: ''
            },

            // Discounts
            isPercentageDiscount: true,
            hasOrderDiscount: false,
            hasProductDiscount: false,

            // Payment
            selectedPaymentMethod: null,
            paymentAmount: 0,

            // Price validation
            priceValidationEnabled: true,
            allowedPriceTypes: [],

            // Filters
            selectedCategory: null,
            selectedBrand: null,
            searchTerm: ''
        };

        this.listeners = new Map();
    }

    /**
     * Get state value
     * @param {string} key - State key
     * @returns {any} State value
     */
    get(key) {
        return this.state[key];
    }

    /**
     * Set state value
     * @param {string} key - State key
     * @param {any} value - New value
     * @param {boolean} notify - Whether to notify listeners (default: true)
     */
    set(key, value, notify = true) {
        const oldValue = this.state[key];
        this.state[key] = value;

        if (notify) {
            this.notifyListeners(key, value, oldValue);
        }
    }

    /**
     * Update multiple state values
     * @param {Object} updates - Object with key-value pairs to update
     * @param {boolean} notify - Whether to notify listeners (default: true)
     */
    update(updates, notify = true) {
        Object.keys(updates).forEach(key => {
            this.set(key, updates[key], false);
        });

        if (notify) {
            Object.keys(updates).forEach(key => {
                this.notifyListeners(key, updates[key], undefined);
            });
        }
    }

    /**
     * Get entire state
     * @returns {Object} Current state
     */
    getAll() {
        return { ...this.state };
    }

    /**
     * Reset state to defaults
     */
    reset() {
        this.state = {
            selectedLocationId: this.state.selectedLocationId, // Keep location
            currentCustomer: null,
            customerTypeId: null,
            currentProductsPage: 1,
            hasMoreProducts: true,
            isLoadingProducts: false,
            allProducts: [],
            stockData: {},
            billingItems: [],
            subtotal: 0,
            totalDiscount: 0,
            taxAmount: 0,
            finalTotal: 0,
            isEditing: false,
            currentEditingSaleId: null,
            isEditingFinalizedSale: false,
            isSalesRep: this.state.isSalesRep, // Keep sales rep status
            salesRepId: this.state.salesRepId,
            salesRepCustomersFiltered: this.state.salesRepCustomersFiltered,
            salesRepVehicleId: this.state.salesRepVehicleId,
            showLoader: false,
            activeModal: null,
            shippingData: {
                shipping_details: '',
                shipping_address: '',
                shipping_charges: 0,
                shipping_status: 'pending',
                delivered_to: '',
                delivery_person: ''
            },
            isPercentageDiscount: true,
            hasOrderDiscount: false,
            hasProductDiscount: false,
            selectedPaymentMethod: null,
            paymentAmount: 0,
            priceValidationEnabled: this.state.priceValidationEnabled,
            allowedPriceTypes: this.state.allowedPriceTypes,
            selectedCategory: null,
            selectedBrand: null,
            searchTerm: ''
        };

        this.notifyListeners('*', this.state, {});
    }

    /**
     * Subscribe to state changes
     * @param {string} key - State key to watch ('*' for all changes)
     * @param {Function} callback - Callback function(newValue, oldValue)
     * @returns {Function} Unsubscribe function
     */
    subscribe(key, callback) {
        if (!this.listeners.has(key)) {
            this.listeners.set(key, []);
        }

        this.listeners.get(key).push(callback);

        // Return unsubscribe function
        return () => {
            const callbacks = this.listeners.get(key);
            const index = callbacks.indexOf(callback);
            if (index > -1) {
                callbacks.splice(index, 1);
            }
        };
    }

    /**
     * Notify listeners of state change
     * @param {string} key - Changed key
     * @param {any} newValue - New value
     * @param {any} oldValue - Old value
     */
    notifyListeners(key, newValue, oldValue) {
        // Notify specific listeners
        if (this.listeners.has(key)) {
            this.listeners.get(key).forEach(callback => {
                callback(newValue, oldValue);
            });
        }

        // Notify wildcard listeners
        if (this.listeners.has('*')) {
            this.listeners.get('*').forEach(callback => {
                callback(key, newValue, oldValue);
            });
        }
    }

    /**
     * Save state to localStorage
     * @param {string} key - Storage key
     */
    saveToStorage(key = 'pos_state') {
        try {
            localStorage.setItem(key, JSON.stringify(this.state));
        } catch (error) {
            console.error('Error saving state to storage:', error);
        }
    }

    /**
     * Load state from localStorage
     * @param {string} key - Storage key
     * @returns {boolean} True if loaded successfully
     */
    loadFromStorage(key = 'pos_state') {
        try {
            const stored = localStorage.getItem(key);
            if (stored) {
                const parsed = JSON.parse(stored);
                this.state = { ...this.state, ...parsed };
                return true;
            }
        } catch (error) {
            console.error('Error loading state from storage:', error);
        }
        return false;
    }
}

// Create singleton instance
export const posState = new POSState();

// Export class for testing
export { POSState };
