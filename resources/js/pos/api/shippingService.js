/**
 * Shipping Service
 * Shipping data management APIs
 */

import apiClient from './apiClient.js';

class ShippingService {
    async saveShippingData(saleId, shippingData) {
        const url = `/sales/${saleId}/shipping`;
        return await apiClient.post(url, shippingData);
    }

    async getShippingData(saleId) {
        const url = `/sales/${saleId}/shipping`;
        return await apiClient.get(url);
    }

    async updateShippingStatus(saleId, status) {
        const url = `/sales/${saleId}/shipping/status`;
        return await apiClient.put(url, { status });
    }
}

export default new ShippingService();
