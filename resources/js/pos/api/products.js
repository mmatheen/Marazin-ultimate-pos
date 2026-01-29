/**
 * Products API
 * All product-related API calls
 */

import { apiClient } from './client.js';
import { cacheManager } from '../utils/cache.js';
import { config } from '../state/config.js';

export class ProductsAPI {
    constructor(client) {
        this.client = client;
    }

    /**
     * Fetch products with pagination
     * @param {Object} params - Query parameters
     * @returns {Promise<Object>} Products data
     */
    async fetchProducts(params = {}) {
        const defaultParams = {
            page: 1,
            per_page: config.pagination.productsPerPage,
            location_id: null,
            category_id: null,
            brand_id: null,
            search: ''
        };

        const queryParams = { ...defaultParams, ...params };

        // Generate cache key
        const cacheKey = JSON.stringify(queryParams);

        // Check cache
        if (cacheManager.has('products', cacheKey)) {
            return cacheManager.get('products', cacheKey);
        }

        // Fetch from API
        const data = await this.client.get('/sell/pos/get-products', queryParams);

        // Cache result
        cacheManager.set('products', cacheKey, data);

        return data;
    }

    /**
     * Search products with autocomplete
     * @param {string} term - Search term
     * @param {number} locationId - Location ID
     * @returns {Promise<Array>} Products array
     */
    async searchProducts(term, locationId = null) {
        const cacheKey = `${term}_${locationId}`;

        // Check search cache
        if (cacheManager.has('search', cacheKey)) {
            return cacheManager.get('search', cacheKey);
        }

        const data = await this.client.get('/products/stocks/autocomplete', {
            search: term,
            location_id: locationId,
            per_page: 50
        });

        // Cache search results
        cacheManager.set('search', cacheKey, data);

        return data;
    }

    /**
     * Get product by ID
     * @param {number} productId - Product ID
     * @param {number} locationId - Location ID
     * @returns {Promise<Object>} Product data
     */
    async getProduct(productId, locationId = null) {
        return this.client.get(`/sell/pos/get-product/${productId}`, {
            location_id: locationId
        });
    }

    /**
     * Get product stock by location
     * @param {number} productId - Product ID
     * @param {number} locationId - Location ID
     * @returns {Promise<Object>} Stock data
     */
    async getProductStock(productId, locationId) {
        return this.client.get(`/sell/pos/product-stock/${productId}`, {
            location_id: locationId
        });
    }

    /**
     * Get product batches
     * @param {number} productId - Product ID
     * @param {number} locationId - Location ID
     * @returns {Promise<Array>} Batches array
     */
    async getProductBatches(productId, locationId) {
        return this.client.get(`/sell/pos/product-batches/${productId}`, {
            location_id: locationId
        });
    }

    /**
     * Get product variations
     * @param {number} productId - Product ID
     * @returns {Promise<Array>} Variations array
     */
    async getProductVariations(productId) {
        return this.client.get(`/sell/pos/product-variations/${productId}`);
    }

    /**
     * Get batch price
     * @param {number} batchId - Batch ID
     * @param {number} customerTypeId - Customer type ID
     * @returns {Promise<Object>} Price data
     */
    async getBatchPrice(batchId, customerTypeId = null) {
        return this.client.get(`/sell/pos/batch-price/${batchId}`, {
            customer_type_id: customerTypeId
        });
    }

    /**
     * Get IMEI numbers for product
     * @param {number} productId - Product ID
     * @param {number} locationId - Location ID
     * @param {number} batchId - Batch ID (optional)
     * @returns {Promise<Array>} IMEI array
     */
    async getIMEINumbers(productId, locationId, batchId = null) {
        return this.client.get('/sell/pos/get-imei-numbers', {
            product_id: productId,
            location_id: locationId,
            batch_id: batchId
        });
    }

    /**
     * Check IMEI availability
     * @param {string} imei - IMEI number
     * @param {number} productId - Product ID
     * @returns {Promise<Object>} Availability data
     */
    async checkIMEI(imei, productId) {
        return this.client.get('/sell/pos/check-imei', {
            imei: imei,
            product_id: productId
        });
    }

    /**
     * Get categories
     * @returns {Promise<Array>} Categories array
     */
    async getCategories() {
        // Check static cache
        if (cacheManager.has('static', 'categories')) {
            return cacheManager.get('static', 'categories');
        }

        const data = await this.client.get('/sell/pos/get-categories');

        // Cache categories
        cacheManager.set('static', 'categories', data);

        return data;
    }

    /**
     * Get brands
     * @returns {Promise<Array>} Brands array
     */
    async getBrands() {
        // Check static cache
        if (cacheManager.has('static', 'brands')) {
            return cacheManager.get('static', 'brands');
        }

        const data = await this.client.get('/sell/pos/get-brands');

        // Cache brands
        cacheManager.set('static', 'brands', data);

        return data;
    }

    /**
     * Clear product cache
     */
    clearCache() {
        cacheManager.clear('products');
        cacheManager.clear('search');
    }
}

// Create singleton instance
export const productsAPI = new ProductsAPI(apiClient);

export default productsAPI;
