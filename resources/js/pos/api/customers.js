/**
 * Customers API
 * All customer-related API calls
 */

import { apiClient } from './client.js';
import { cacheManager } from '../utils/cache.js';

export class CustomersAPI {
    constructor(client) {
        this.client = client;
    }

    /**
     * Fetch customers with pagination
     * @param {Object} params - Query parameters
     * @returns {Promise<Object>} Customers data
     */
    async fetchCustomers(params = {}) {
        const defaultParams = {
            page: 1,
            per_page: 50,
            search: '',
            route_id: null,
            sales_rep_id: null
        };

        const queryParams = { ...defaultParams, ...params };

        // Generate cache key
        const cacheKey = JSON.stringify(queryParams);

        // Check cache
        if (cacheManager.has('customers', cacheKey)) {
            return cacheManager.get('customers', cacheKey);
        }

        // Fetch from API
        const data = await this.client.get('/sell/pos/get-customers', queryParams);

        // Cache result
        cacheManager.set('customers', cacheKey, data);

        return data;
    }

    /**
     * Search customers with autocomplete
     * @param {string} term - Search term
     * @param {Object} filters - Additional filters
     * @returns {Promise<Array>} Customers array
     */
    async searchCustomers(term, filters = {}) {
        const cacheKey = `${term}_${JSON.stringify(filters)}`;

        // Check search cache
        if (cacheManager.has('search', cacheKey)) {
            return cacheManager.get('search', cacheKey);
        }

        const data = await this.client.get('/sell/pos/search-customers', {
            term: term,
            ...filters
        });

        // Cache search results
        cacheManager.set('search', cacheKey, data);

        return data;
    }

    /**
     * Get customer by ID
     * @param {number} customerId - Customer ID
     * @returns {Promise<Object>} Customer data
     */
    async getCustomer(customerId) {
        const cacheKey = `customer_${customerId}`;

        // Check cache
        if (cacheManager.has('customers', cacheKey)) {
            return cacheManager.get('customers', cacheKey);
        }

        const data = await this.client.get(`/customer-get-by-id/${customerId}`);

        // Cache customer
        cacheManager.set('customers', cacheKey, data);

        return data;
    }

    /**
     * Get customer type
     * @param {number} customerId - Customer ID
     * @returns {Promise<Object>} Customer type data
     */
    async getCustomerType(customerId) {
        return this.client.get(`/sell/pos/customer-type/${customerId}`);
    }

    /**
     * Get customer price for product
     * @param {number} customerId - Customer ID
     * @param {number} productId - Product ID
     * @param {number} customerTypeId - Customer type ID
     * @returns {Promise<Object>} Price data
     */
    async getCustomerPrice(customerId, productId, customerTypeId = null) {
        return this.client.get('/sell/pos/customer-price', {
            customer_id: customerId,
            product_id: productId,
            customer_type_id: customerTypeId
        });
    }

    /**
     * Get customer previous price for product
     * @param {number} customerId - Customer ID
     * @param {number} productId - Product ID
     * @returns {Promise<Object>} Previous price data
     */
    async getCustomerPreviousPrice(customerId, productId) {
        return this.client.get('/sell/pos/customer-previous-price', {
            customer_id: customerId,
            product_id: productId
        });
    }

    /**
     * Get customer credit limit
     * @param {number} customerId - Customer ID
     * @returns {Promise<Object>} Credit limit data
     */
    async getCustomerCreditLimit(customerId) {
        return this.client.get(`/sell/pos/customer-credit-limit/${customerId}`);
    }

    /**
     * Get customer outstanding balance
     * @param {number} customerId - Customer ID
     * @returns {Promise<Object>} Balance data
     */
    async getCustomerBalance(customerId) {
        return this.client.get(`/sell/pos/customer-balance/${customerId}`);
    }

    /**
     * Get customers by route
     * @param {number} routeId - Route ID
     * @returns {Promise<Array>} Customers array
     */
    async getCustomersByRoute(routeId) {
        return this.client.get('/sell/pos/customers-by-route', {
            route_id: routeId
        });
    }

    /**
     * Get customers by sales rep
     * @param {number} salesRepId - Sales rep ID
     * @returns {Promise<Array>} Customers array
     */
    async getCustomersBySalesRep(salesRepId) {
        return this.client.get('/sell/pos/customers-by-sales-rep', {
            sales_rep_id: salesRepId
        });
    }

    /**
     * Create new customer
     * @param {Object} customerData - Customer data
     * @returns {Promise<Object>} Created customer
     */
    async createCustomer(customerData) {
        const data = await this.client.post('/sell/pos/create-customer', customerData);

        // Clear cache
        this.clearCache();

        return data;
    }

    /**
     * Update customer
     * @param {number} customerId - Customer ID
     * @param {Object} customerData - Updated customer data
     * @returns {Promise<Object>} Updated customer
     */
    async updateCustomer(customerId, customerData) {
        const data = await this.client.put(`/sell/pos/update-customer/${customerId}`, customerData);

        // Clear cache
        this.clearCache();

        return data;
    }

    /**
     * Get routes
     * @returns {Promise<Array>} Routes array
     */
    async getRoutes() {
        // Check static cache
        if (cacheManager.has('static', 'routes')) {
            return cacheManager.get('static', 'routes');
        }

        const data = await this.client.get('/sell/pos/get-routes');

        // Cache routes
        cacheManager.set('static', 'routes', data);

        return data;
    }

    /**
     * Clear customer cache
     */
    clearCache() {
        cacheManager.clear('customers');
    }
}

// Create singleton instance
export const customersAPI = new CustomersAPI(apiClient);

export default customersAPI;
