/**
 * Location Service
 * All location-related API calls
 * Extracted from lines 501-900 of monolithic pos_ajax.blade.php
 */

import apiClient from './apiClient.js';
import { POSState, POSConfig } from '../core/config.js';

class LocationService {
    /**
     * Fetch all locations with caching
     */
    async fetchAllLocations(forceRefresh = false) {
        // Check cache first
        if (!forceRefresh &&
            POSState.cachedLocations &&
            POSState.locationCacheExpiry > Date.now()) {
            console.log('✅ Location cache hit');
            return POSState.cachedLocations;
        }

        const url = '/locations/all';

        try {
            const data = await apiClient.get(url);

            // Cache results
            POSState.cachedLocations = data;
            POSState.locationCacheExpiry = Date.now() + POSConfig.cache.locationExpiry;

            console.log('📍 Locations fetched and cached:', data.length);
            return data;
        } catch (error) {
            console.error('Failed to fetch locations:', error);
            throw error;
        }
    }

    /**
     * Get location by ID
     */
    async getLocationById(locationId) {
        // Check cache first
        if (POSState.cachedLocations) {
            const cached = POSState.cachedLocations.find(loc => loc.id === parseInt(locationId));
            if (cached) return cached;
        }

        const url = `/locations/${locationId}`;

        try {
            return await apiClient.get(url);
        } catch (error) {
            console.error('Failed to fetch location:', error);
            throw error;
        }
    }

    /**
     * Get sub-locations for a parent location
     */
    async getSubLocations(parentLocationId) {
        const url = `/locations/${parentLocationId}/sub-locations`;

        try {
            return await apiClient.get(url);
        } catch (error) {
            console.error('Failed to fetch sub-locations:', error);
            return [];
        }
    }

    /**
     * Check if location is a parent location
     */
    isParentLocation(location) {
        return location && location.is_parent === 1 && location.has_sub_locations === true;
    }

    /**
     * Check if location is a sub-location
     */
    isSubLocation(location) {
        return location && location.parent_location_id !== null;
    }
}

const locationService = new LocationService();
export default locationService;
