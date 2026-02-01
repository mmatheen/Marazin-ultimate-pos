/**
 * Category Service
 * Category and subcategory API endpoints
 */

import apiClient from './apiClient.js';
import cacheManager from '../cache/cacheManager.js';

class CategoryService {
    async fetchCategories() {
        const cached = cacheManager.getCachedStaticData('categories');
        if (cached) return cached;

        const url = '/categories/all';
        const data = await apiClient.get(url);

        cacheManager.setCachedStaticData('categories', data);
        return data;
    }

    async fetchSubcategories(categoryId) {
        const cacheKey = `subcategories_${categoryId}`;
        const cached = cacheManager.getCachedStaticData(cacheKey);
        if (cached) return cached;

        const url = `/categories/${categoryId}/subcategories`;
        const data = await apiClient.get(url);

        cacheManager.setCachedStaticData(cacheKey, data);
        return data;
    }
}

export default new CategoryService();
