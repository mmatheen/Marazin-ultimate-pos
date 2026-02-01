/**
 * Product Service
 * All product-related API calls
 * Extracted from lines 2900-3200 of monolithic pos_ajax.blade.php
 */

import apiClient from './apiClient.js';
import { POSState, POSConfig } from '../core/config.js';
import cacheManager from '../cache/cacheManager.js';

class ProductService {
    /**
     * Fetch paginated products with stock filtering
     */
    async fetchPaginatedProducts(locationId, page = 1, perPage = null) {
        if (!locationId) {
            throw new Error('Location ID is required');
        }

        perPage = perPage || POSConfig.pagination.perPage;

        const url = `/products/stocks?location_id=${locationId}&page=${page}&per_page=${perPage}&with_stock=1`;

        try {
            const data = await apiClient.get(url);

            if (!data || data.status !== 200 || !Array.isArray(data.data)) {
                console.warn('⚠️ Invalid data structure received:', data);
                return {products: [], hasMore: false};
            }

            return {
                products: data.data,
                hasMore: data.data.length === perPage,
                totalPages: data.total_pages || 1
            };
        } catch (error) {
            console.error('Failed to fetch products:', error);
            throw error;
        }
    }

    /**
     * Search products by term
     */
    async searchProducts(term, locationId) {
        // Check cache first
        const cached = cacheManager.getCachedSearch(term);
        if (cached) return cached;

        const url = `/products/search?term=${encodeURIComponent(term)}&location_id=${locationId}`;

        try {
            const data = await apiClient.get(url);

            // Cache results
            cacheManager.setCachedSearch(term, data);

            return data;
        } catch (error) {
            console.error('Product search failed:', error);
            throw error;
        }
    }

    /**
     * Fetch product stock details
     */
    async fetchProductStock(productId, locationId) {
        const url = `/products/${productId}/stock?location_id=${locationId}`;

        try {
            return await apiClient.get(url);
        } catch (error) {
            console.error('Failed to fetch product stock:', error);
            throw error;
        }
    }

    /**
     * Fetch filtered products (category/subcategory/brand)
     */
    async fetchFilteredProducts(filterType, filterId, locationId, page = 1, perPage = null) {
        perPage = perPage || POSConfig.pagination.perPage;

        const url = `/products/filter/${filterType}/${filterId}?location_id=${locationId}&page=${page}&per_page=${perPage}&with_stock=1`;

        try {
            const data = await apiClient.get(url);

            if (!data || !Array.isArray(data.data)) {
                return {products: [], hasMore: false};
            }

            return {
                products: data.data,
                hasMore: data.data.length === perPage
            };
        } catch (error) {
            console.error('Failed to fetch filtered products:', error);
            throw error;
        }
    }

    /**
     * Quick add product (create new product from POS)
     */
    async quickAddProduct(productData) {
        const url = '/products/quick-add';

        try {
            return await apiClient.post(url, productData);
        } catch (error) {
            console.error('Failed to quick add product:', error);
            throw error;
        }
    }

    /**
     * Check if product exists by barcode/SKU
     */
    async checkProductExists(searchTerm) {
        const url = `/product/check-sku`;

        try {
            return await apiClient.post(url, { sku: searchTerm });
        } catch (error) {
            console.error('Failed to check product existence:', error);
            return { exists: false };
        }
    }

    /**
     * Fetch single product stock details (API variant)
     */
    async fetchProductStockDetail(productId, locationId) {
        const url = `/api/products/stocks?location_id=${locationId}&product_id=${productId}`;

        try {
            return await apiClient.get(url);
        } catch (error) {
            console.error('Failed to fetch product stock detail:', error);
            throw error;
        }
    }

    /**
     * Search products using API autocomplete endpoint (GET variant)
     */
    async searchProductsAPI(searchTerm, locationId) {
        const url = `/api/products/stocks/autocomplete?search=${encodeURIComponent(searchTerm)}&location_id=${locationId}`;

        try {
            return await apiClient.get(url);
        } catch (error) {
            console.error('API product search failed:', error);
            throw error;
        }
    }
}

const productService = new ProductService();
export default productService;
