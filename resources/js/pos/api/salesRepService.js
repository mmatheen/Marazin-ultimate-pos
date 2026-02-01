/**
 * Sales Rep Service
 * Sales representative assignment and routing APIs
 */

import apiClient from './apiClient.js';

class SalesRepService {
    async getMyAssignments() {
        const url = '/sales-rep/my-assignments';
        return await apiClient.get(url);
    }

    async getRouteDetails(routeId) {
        const url = `/sales-rep/routes/${routeId}`;
        return await apiClient.get(url);
    }

    async getVehicleDetails(vehicleId) {
        const url = `/sales-rep/vehicles/${vehicleId}`;
        return await apiClient.get(url);
    }
}

export default new SalesRepService();
