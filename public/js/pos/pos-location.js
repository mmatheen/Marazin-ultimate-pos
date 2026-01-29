/**
 * POS Location Manager
 * Handles all location-related functionality
 */

class POSLocationManager {
    constructor(cache) {
        this.cache = cache;
        this.selectedLocationId = null;
        this.locations = [];
    }

    /**
     * Fetch all locations
     */
    async fetchLocations(forceRefresh = false, callback = null) {
        // Check cache first
        if (!forceRefresh) {
            const cachedLocations = this.cache.getCachedStaticData('locations');
            if (cachedLocations) {
                console.log('✅ Using cached locations');
                this.locations = cachedLocations;
                this.populateDropdown(cachedLocations);
                if (callback) callback();
                return cachedLocations;
            }
        }

        console.log('🔄 Fetching locations from server...');

        try {
            const response = await fetch('/location-get-all', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            });

            const data = await response.json();

            if (data.status === 200 && Array.isArray(data.data)) {
                this.locations = data.data;

                // Cache locations
                this.cache.setCachedStaticData('locations', data.data);

                // Populate dropdown
                this.populateDropdown(data.data);

                console.log(`✅ Loaded ${data.data.length} locations`);

                if (callback) callback();
                return data.data;
            } else {
                throw new Error('Invalid response format');
            }
        } catch (error) {
            console.error('❌ Error fetching locations:', error);
            if (typeof toastr !== 'undefined') {
                toastr.error('Failed to load locations', 'Error');
            }
            return [];
        }
    }

    /**
     * Populate location dropdown
     */
    populateDropdown(locations) {
        const dropdowns = [
            document.getElementById('locationSelect'),
            document.getElementById('locationSelectDesktop')
        ];

        dropdowns.forEach(dropdown => {
            if (!dropdown) return;

            // Clear existing options except the first one (placeholder)
            while (dropdown.options.length > 1) {
                dropdown.remove(1);
            }

            // Add locations
            locations.forEach(location => {
                const option = document.createElement('option');
                option.value = location.id;
                option.textContent = location.name;
                dropdown.appendChild(option);
            });

            console.log(`✅ Populated location dropdown: ${dropdown.id}`);
        });
    }

    /**
     * Handle location change
     */
    async handleLocationChange(locationId, productManager) {
        if (!locationId) {
            console.log('No location selected');
            return;
        }

        console.log('📍 Location changed to:', locationId);
        this.selectedLocationId = locationId;
        window.selectedLocationId = locationId;

        // Sync both dropdowns
        this.syncDropdowns(locationId);

        // Show/hide product list area
        const productListArea = document.getElementById('productListArea');
        const mainContent = document.getElementById('mainContent');

        if (locationId) {
            if (productListArea) {
                productListArea.classList.remove('d-none');
                productListArea.classList.add('show');
            }
            if (mainContent) {
                mainContent.classList.remove('col-md-12');
                mainContent.classList.add('col-md-7');
            }

            // Fetch products for selected location
            if (productManager) {
                await productManager.fetchProducts(locationId, true);
            }
        } else {
            if (productListArea) {
                productListArea.classList.add('d-none');
                productListArea.classList.remove('show');
            }
            if (mainContent) {
                mainContent.classList.add('col-md-12');
                mainContent.classList.remove('col-md-7');
            }
        }

        // Store in localStorage for persistence
        try {
            localStorage.setItem('lastSelectedLocation', locationId);
        } catch (e) {
            console.warn('Could not save location to localStorage:', e);
        }

        // Trigger custom event
        window.dispatchEvent(new CustomEvent('locationChanged', {
            detail: { locationId, location: this.getLocationById(locationId) }
        }));
    }

    /**
     * Sync both dropdown values
     */
    syncDropdowns(locationId) {
        const dropdowns = [
            document.getElementById('locationSelect'),
            document.getElementById('locationSelectDesktop')
        ];

        dropdowns.forEach(dropdown => {
            if (dropdown && dropdown.value !== locationId) {
                dropdown.value = locationId;
            }
        });
    }

    /**
     * Get location by ID
     */
    getLocationById(locationId) {
        return this.locations.find(loc => String(loc.id) === String(locationId));
    }

    /**
     * Get location name by ID
     */
    getLocationName(locationId) {
        const location = this.getLocationById(locationId);
        return location ? location.name : 'Unknown Location';
    }

    /**
     * Auto-select location (for sales rep or edit mode)
     */
    autoSelectLocation(locationId, maxRetries = 20) {
        let retryCount = 0;

        const attemptSelection = () => {
            const desktopDropdown = document.getElementById('locationSelectDesktop');
            const mobileDropdown = document.getElementById('locationSelect');

            const dropdown = desktopDropdown || mobileDropdown;

            if (!dropdown || dropdown.options.length <= 1) {
                if (retryCount < maxRetries) {
                    retryCount++;
                    console.log(`Location dropdown not ready, retry ${retryCount}/${maxRetries}...`);
                    setTimeout(attemptSelection, 100);
                } else {
                    console.error('❌ Failed to auto-select location after maximum retries');
                }
                return;
            }

            // Find the location option
            const locationOption = Array.from(dropdown.options).find(
                opt => String(opt.value) === String(locationId)
            );

            if (locationOption) {
                dropdown.value = locationId;
                this.syncDropdowns(locationId);
                this.selectedLocationId = locationId;
                window.selectedLocationId = locationId;

                // Trigger change event
                dropdown.dispatchEvent(new Event('change', { bubbles: true }));

                console.log(`✅ Auto-selected location: ${locationOption.textContent} (ID: ${locationId})`);
            } else if (retryCount < maxRetries) {
                retryCount++;
                console.log(`Location option not found, retry ${retryCount}/${maxRetries}...`);
                setTimeout(attemptSelection, 100);
            } else {
                console.error(`❌ Location ${locationId} not found in dropdown after ${maxRetries} retries`);
            }
        };

        // Start the selection process
        setTimeout(attemptSelection, 200);
    }

    /**
     * Restore last selected location
     */
    restoreLastLocation() {
        try {
            const lastLocation = localStorage.getItem('lastSelectedLocation');
            if (lastLocation) {
                this.autoSelectLocation(lastLocation);
                return true;
            }
        } catch (e) {
            console.warn('Could not restore last location:', e);
        }
        return false;
    }

    /**
     * Check if location has product access
     */
    async checkLocationAccess(locationId, productId) {
        try {
            const response = await fetch('/sell/pos/check-location-access', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({
                    location_id: locationId,
                    product_id: productId
                })
            });

            const data = await response.json();
            return data.has_access || false;
        } catch (error) {
            console.error('Error checking location access:', error);
            return false;
        }
    }

    /**
     * Setup location change listeners
     */
    setupListeners(productManager) {
        const handleChange = (e) => {
            const locationId = e.target.value;
            this.handleLocationChange(locationId, productManager);
        };

        const desktopDropdown = document.getElementById('locationSelectDesktop');
        const mobileDropdown = document.getElementById('locationSelect');

        if (desktopDropdown) {
            desktopDropdown.addEventListener('change', handleChange);
            console.log('✅ Desktop location dropdown listener attached');
        }

        if (mobileDropdown) {
            mobileDropdown.addEventListener('change', handleChange);
            console.log('✅ Mobile location dropdown listener attached');
        }
    }

    /**
     * Filter locations by sales rep assignment
     */
    filterLocationsBySalesRep(vehicleId) {
        if (!vehicleId) {
            console.warn('No vehicle ID provided for location filtering');
            return;
        }

        console.log('Filtering locations by vehicle:', vehicleId);

        const dropdowns = [
            document.getElementById('locationSelect'),
            document.getElementById('locationSelectDesktop')
        ];

        dropdowns.forEach(dropdown => {
            if (!dropdown) return;

            // Find all options
            Array.from(dropdown.options).forEach(option => {
                if (option.value === '') return; // Skip placeholder

                // Check if this location matches the vehicle
                const location = this.getLocationById(option.value);

                // Show only sublocations matching the vehicle
                if (location && location.vehicle_id && String(location.vehicle_id) === String(vehicleId)) {
                    option.style.display = '';
                } else if (option.value !== '') {
                    option.style.display = 'none';
                }
            });
        });

        console.log('Location filtering applied for vehicle:', vehicleId);
    }

    /**
     * Reset location filter (show all)
     */
    resetLocationFilter() {
        const dropdowns = [
            document.getElementById('locationSelect'),
            document.getElementById('locationSelectDesktop')
        ];

        dropdowns.forEach(dropdown => {
            if (!dropdown) return;

            Array.from(dropdown.options).forEach(option => {
                option.style.display = '';
            });
        });

        console.log('Location filter reset - showing all locations');
    }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = POSLocationManager;
} else {
    window.POSLocationManager = POSLocationManager;
}
