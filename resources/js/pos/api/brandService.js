/**
 * Brand Service
 * Brand API endpoints
 */

import apiClient from './apiClient.js';
import cacheManager from '../cache/cacheManager.js';

class BrandService {
    async fetchBrands() {
        const cached = cacheManager.getCachedStaticData('brands');
        if (cached) return cached;

        const url = '/brands/all';
        const data = await apiClient.get(url);

        cacheManager.setCachedStaticData('brands', data);
        return data;
    }

    async getBrandById(brandId) {
        const url = `/brands/${brandId}`;
        return await apiClient.get(url);
    }
}

export default new BrandService();
