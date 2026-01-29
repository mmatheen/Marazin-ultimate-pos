/**
 * Location Manager Module
 * Handles location loading and dropdown population - exactly as in pos_ajax.blade.php
 */

import { posState } from '../state/index.js';
import { apiClient } from '../api/client.js';

export class LocationManager {
    constructor() {
        this.cachedLocations = null;
        this.locationCacheExpiry = null;
        this.LOCATION_CACHE_DURATION = 5 * 60 * 1000; // 5 minutes
        this.locationSelect = null;
        this.locationSelectDesktop = null;
    }

    /**
     * Initialize location manager
     */
    initialize() {
        this.locationSelect = document.getElementById('locationSelect');
        this.locationSelectDesktop = document.getElementById('locationSelectDesktop');

        if (this.locationSelect && this.locationSelectDesktop) {
            this.setupLocationChangeHandlers();
            this.loadLocations();
        } else {
            console.error('Location select elements not found');
        }
    }

    /**
     * Load locations from server or cache
     */
    async loadLocations(callback) {
        // Check cache first
        if (this.cachedLocations && this.locationCacheExpiry && Date.now() < this.locationCacheExpiry) {
            console.log('✅ Using cached locations');
            this.populateLocationDropdown(this.cachedLocations);
            if (typeof callback === 'function') {
                callback();
            }
            return;
        }

        try {
            console.log('🔄 Fetching locations from server...');
            const response = await apiClient.get('/location-get-all');

            // Check for status = true and data exists
            if (response.status && Array.isArray(response.data)) {
                // Cache the locations
                this.cachedLocations = response.data;
                this.locationCacheExpiry = Date.now() + this.LOCATION_CACHE_DURATION;
                console.log('💾 Locations cached for 5 minutes');

                this.populateLocationDropdown(response.data);

                // Execute callback if provided
                if (typeof callback === 'function') {
                    callback();
                }
            } else {
                console.error('Error fetching locations:', response.message);
            }
        } catch (error) {
            console.error('Error loading locations:', error);
        }
    }

    /**
     * Populate location dropdown - EXACT copy from pos_ajax.blade.php
     */
    populateLocationDropdown(locations) {
        const $locationSelect = $(this.locationSelect);
        const $locationSelectDesktop = $(this.locationSelectDesktop);

        $locationSelect.empty(); // Clear existing options
        $locationSelectDesktop.empty(); // Clear desktop options too

        // Add default prompt
        $locationSelect.append('<option value="" disabled selected>Select Location</option>');
        $locationSelectDesktop.append('<option value="" disabled selected>Select Location</option>');

        // Separate parent and sub-locations for better organization
        const parentLocations = locations.filter(loc => !loc.parent_id);
        const subLocations = locations.filter(loc => loc.parent_id);

        // Add parent locations first
        parentLocations.forEach((location) => {
            let displayName = location.name;
            // If this parent has children in the list, show count
            const childCount = subLocations.filter(sub => sub.parent_id === location.id).length;
            if (childCount > 0) {
                displayName += ` (Main Location - ${childCount} vehicles)`;
            }

            const option = $('<option></option>').val(location.id).text(displayName);
            const optionDesktop = $('<option></option>').val(location.id).text(displayName);

            $locationSelect.append(option);
            $locationSelectDesktop.append(optionDesktop);
        });

        // Add sub-locations with parent reference
        subLocations.forEach((location) => {
            let displayName = location.name;

            // Add parent info and vehicle details if available
            if (location.parent && location.parent.name) {
                displayName = `${location.parent.name} → ${location.name}`;
            }
            if (location.vehicle_number) {
                displayName += ` (${location.vehicle_number})`;
            }
            if (location.vehicle_type) {
                displayName += ` - ${location.vehicle_type}`;
            }

            const option = $('<option></option>').val(location.id).text(displayName);
            const optionDesktop = $('<option></option>').val(location.id).text(displayName);

            $locationSelect.append(option);
            $locationSelectDesktop.append(optionDesktop);
        });

        console.log('📋 Location dropdown populated with', locations.length, 'locations');

        // Auto-select first location if available
        if (locations.length > 0) {
            const firstLocationId = locations[0].id;
            $locationSelect.val(firstLocationId);
            $locationSelectDesktop.val(firstLocationId);

            // Trigger change to initialize everything
            setTimeout(() => {
                this.handleLocationChange(firstLocationId);
                console.log('✅ Auto-selected first location:', firstLocationId);
            }, 300);
        }
    }

    /**
     * Setup location change handlers - synced between mobile and desktop
     */
    setupLocationChangeHandlers() {
        const self = this;

        // Mobile location change
        $(this.locationSelect).on('change', function() {
            const locationId = $(this).val();
            self.handleLocationChange(locationId);

            // Sync with desktop
            $(self.locationSelectDesktop).val(locationId);
        });

        // Desktop location change
        $(this.locationSelectDesktop).on('change', function() {
            const locationId = $(this).val();
            self.handleLocationChange(locationId);

            // Sync with mobile
            $(self.locationSelect).val(locationId);
        });

        console.log('✅ Location change handlers setup');
    }

    /**
     * Handle location change - EXACT copy from pos_ajax.blade.php
     */
    handleLocationChange(locationId) {
        console.log(`📍 Location changed to: ${locationId}`);

        // Update state
        posState.set('selectedLocationId', locationId);
        window.selectedLocationId = locationId;

        // Show/hide product list area
        const productListArea = document.getElementById('productListArea');
        const mainContent = document.getElementById('mainContent');

        if (locationId) {
            if (productListArea && mainContent) {
                productListArea.classList.remove('d-none');
                productListArea.classList.add('show');
                mainContent.classList.remove('col-md-12');
                mainContent.classList.add('col-md-7');
                console.log('✅ Product list area displayed with proper layout');
            }
        } else {
            if (productListArea && mainContent) {
                productListArea.classList.add('d-none');
                productListArea.classList.remove('show');
                mainContent.classList.remove('col-md-7');
                mainContent.classList.add('col-md-12');
                console.log('ℹ️ Product list area hidden with full width layout');
            }
        }

        // Clear billing table when location changes
        const billingBody = document.getElementById('billing-body');
        if (billingBody) {
            billingBody.innerHTML = '';
            console.log('🗑️ Billing body cleared due to location change');
        }

        // Auto-focus search input after location change
        setTimeout(() => {
            const productSearchInput = document.getElementById('productSearchInput');
            if (productSearchInput) {
                productSearchInput.focus();
                console.log('Product search input focused after location change');
            }
        }, 300);

        // Trigger event for other modules
        window.dispatchEvent(new CustomEvent('locationChanged', {
            detail: { locationId }
        }));
    }

    /**
     * Get selected location ID
     */
    getSelectedLocationId() {
        return posState.get('selectedLocationId');
    }

    /**
     * Get cached locations
     */
    getLocations() {
        return this.cachedLocations || [];
    }
}

export const locationManager = new LocationManager();
export default locationManager;
