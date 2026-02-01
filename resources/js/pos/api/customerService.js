/**
 * Customer Service
 * All customer-related API calls
 * Extracted from lines 4101-4350 of monolithic pos_ajax.blade.php
 */

import apiClient from './apiClient.js';
import cacheManager from '../cache/cacheManager.js';

class CustomerService {
    /**
     * Get customer by ID with caching
     */
    async getCustomerById(customerId) {
        // Check cache first
        const cached = cacheManager.getCachedCustomer(customerId);
        if (cached) {
            console.log('✅ Customer cache hit:', customerId);
            return cached;
        }

        const url = `/customer-get-by-id/${customerId}`;

        try {
            const data = await apiClient.get(url);

            // Cache the result
            cacheManager.setCachedCustomer(customerId, data);

            return data;
        } catch (error) {
            console.error('Failed to fetch customer:', error);
            throw error;
        }
    }

    /**
     * Filter customers by city IDs (for sales rep routing)
     */
    async filterCustomersByCities(cityIds) {
        const url = '/customers/filter-by-cities';

        try {
            return await apiClient.post(url, { city_ids: cityIds });
        } catch (error) {
            console.error('Failed to filter customers by cities:', error);
            throw error;
        }
    }

    /**
     * Get customer's previous purchase price for a product
     */
    async getCustomerPreviousPrice(customerId, productId) {
        // Check cache first
        const cached = cacheManager.getCachedCustomerPrice(customerId, productId);
        if (cached) {
            console.log('✅ Customer price cache hit');
            return cached;
        }

        const url = `/customers/${customerId}/previous-price/${productId}`;

        try {
            const data = await apiClient.get(url);

            // Cache the result
            if (data && data.price) {
                cacheManager.setCachedCustomerPrice(customerId, productId, data.price);
            }

            return data;
        } catch (error) {
            console.error('Failed to fetch previous price:', error);
            return { price: null };
        }
    }

    /**
     * Get customer credit information
     */
    async getCustomerCredit(customerId) {
        const url = `/customers/${customerId}/credit`;

        try {
            return await apiClient.get(url);
        } catch (error) {
            console.error('Failed to fetch customer credit:', error);
            return { credit_limit: 0, used_credit: 0, available_credit: 0 };
        }
    }

    /**
     * Get all customers (for dropdown population)
     */
    async getAllCustomers() {
        const url = '/customers/all';

        try {
            return await apiClient.get(url);
        } catch (error) {
            console.error('Failed to fetch all customers:', error);
            throw error;
        }
    }
}

const customerService = new CustomerService();
export default customerService;
