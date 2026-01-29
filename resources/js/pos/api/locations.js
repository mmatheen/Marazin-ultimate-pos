/**
 * Locations API
 * All location-related API calls
 */

import { apiClient } from './client.js';
import { cacheManager } from '../utils/cache.js';

export class LocationsAPI {
    constructor(client) {
        this.client = client;
    }

    /**
     * Get all locations
     * @returns {Promise<Array>} Locations array
     */
    async getLocations() {
        // Check static cache
        if (cacheManager.has('static', 'locations')) {
            return cacheManager.get('static', 'locations');
        }

        const data = await this.client.get('/location-get-all');

        // Cache locations
        cacheManager.set('static', 'locations', data);

        return data;
    }

    /**
     * Get location by ID
     * @param {number} locationId - Location ID
     * @returns {Promise<Object>} Location data
     */
    async getLocation(locationId) {
        return this.client.get(`/sell/pos/get-location/${locationId}`);
    }

    /**
     * Get locations accessible by user
     * @param {number} userId - User ID
     * @returns {Promise<Array>} Accessible locations
     */
    async getUserLocations(userId = null) {
        return this.client.get('/sell/pos/user-locations', {
            user_id: userId
        });
    }

    /**
     * Get locations by sales rep
     * @param {number} salesRepId - Sales rep ID
     * @returns {Promise<Array>} Locations array
     */
    async getLocationsBySalesRep(salesRepId) {
        return this.client.get('/sell/pos/sales-rep-locations', {
            sales_rep_id: salesRepId
        });
    }

    /**
     * Get location stock summary
     * @param {number} locationId - Location ID
     * @returns {Promise<Object>} Stock summary
     */
    async getLocationStockSummary(locationId) {
        return this.client.get(`/sell/pos/location-stock/${locationId}`);
    }

    /**
     * Check product availability at location
     * @param {number} productId - Product ID
     * @param {number} locationId - Location ID
     * @param {number} quantity - Required quantity
     * @returns {Promise<Object>} Availability data
     */
    async checkProductAvailability(productId, locationId, quantity) {
        return this.client.get('/sell/pos/check-availability', {
            product_id: productId,
            location_id: locationId,
            quantity: quantity
        });
    }

    /**
     * Get location settings
     * @param {number} locationId - Location ID
     * @returns {Promise<Object>} Location settings
     */
    async getLocationSettings(locationId) {
        return this.client.get(`/sell/pos/location-settings/${locationId}`);
    }

    /**
     * Get cash registers for location
     * @param {number} locationId - Location ID
     * @returns {Promise<Array>} Cash registers array
     */
    async getCashRegisters(locationId) {
        return this.client.get('/sell/pos/cash-registers', {
            location_id: locationId
        });
    }

    /**
     * Get active cash register
     * @param {number} locationId - Location ID
     * @returns {Promise<Object>} Active cash register
     */
    async getActiveCashRegister(locationId) {
        return this.client.get('/sell/pos/active-cash-register', {
            location_id: locationId
        });
    }

    /**
     * Open cash register
     * @param {number} registerId - Register ID
     * @param {Object} data - Opening data
     * @returns {Promise<Object>} Opened register
     */
    async openCashRegister(registerId, data) {
        return this.client.post(`/sell/pos/open-register/${registerId}`, data);
    }

    /**
     * Close cash register
     * @param {number} registerId - Register ID
     * @param {Object} data - Closing data
     * @returns {Promise<Object>} Closed register
     */
    async closeCashRegister(registerId, data) {
        return this.client.post(`/sell/pos/close-register/${registerId}`, data);
    }

    /**
     * Clear location cache
     */
    clearCache() {
        cacheManager.delete('static', 'locations');
    }
}

// Create singleton instance
export const locationsAPI = new LocationsAPI(apiClient);

export default locationsAPI;
