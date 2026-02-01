/**
 * IMEI Service
 * IMEI tracking and management APIs
 */

import apiClient from './apiClient.js';

class ImeiService {
    async getAvailableImeis(productId, locationId, batchId = null) {
        let url = `/imeis/available?product_id=${productId}&location_id=${locationId}`;
        if (batchId) url += `&batch_id=${batchId}`;

        return await apiClient.get(url);
    }

    async checkImeiDuplicate(imeiNumber) {
        const url = `/imeis/check-duplicate?imei=${encodeURIComponent(imeiNumber)}`;
        return await apiClient.get(url);
    }

    async reserveImeis(imeiNumbers) {
        const url = '/imeis/reserve';
        return await apiClient.post(url, { imeis: imeiNumbers });
    }

    async releaseImeis(imeiNumbers) {
        const url = '/imeis/release';
        return await apiClient.post(url, { imeis: imeiNumbers });
    }
}

export default new ImeiService();
