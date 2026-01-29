/**
 * POS Sales Rep Manager
 * Handles sales rep functionality including vehicle/route selection and customer filtering
 */

class POSSalesRepManager {
    constructor(cache, customerManager, locationManager) {
        this.cache = cache;
        this.customerManager = customerManager;
        this.locationManager = locationManager;
        this.isSalesRep = false;
        this.assignments = null;
        this.currentSelection = null;
    }

    /**
     * Check if user is a sales rep
     */
    async checkStatus(callback) {
        console.log('Checking sales rep status...');

        try {
            const response = await fetch('/sales-rep/my-assignments', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            });

            const data = await response.json();

            if (data.is_sales_rep) {
                this.isSalesRep = true;
                this.assignments = data.assignments;
                window.isSalesRep = true;
                window.salesRepAssignments = data.assignments;

                console.log('✅ User is a sales rep with assignments:', data.assignments);

                // Handle sales rep user
                this.handleSalesRepUser(data.assignments);

                if (callback) callback(true);
            } else {
                this.isSalesRep = false;
                window.isSalesRep = false;
                console.log('User is not a sales rep');

                // Hide sales rep display
                this.hideSalesRepDisplay();

                if (callback) callback(false);
            }
        } catch (error) {
            console.error('Error checking sales rep status:', error);
            this.isSalesRep = false;
            window.isSalesRep = false;
            if (callback) callback(false);
        }
    }

    /**
     * Handle sales rep user
     */
    handleSalesRepUser(assignments) {
        console.log('Handling sales rep user with assignments:', assignments);

        // Store assignments globally
        window.salesRepAssignments = assignments;

        // Check if we already have a valid selection
        const storedSelection = this.getSavedSelection();
        const currentUserId = window.authUserId || null;

        // Validate stored selection belongs to current user
        if (storedSelection && storedSelection.userId && storedSelection.userId !== currentUserId) {
            console.log('🗑️ Clearing stored selection from different user');
            this.clearSelection();
        }

        if (!this.hasValidSelection()) {
            // Show selection modal
            this.showSelectionModal(assignments);
        } else {
            // Use stored selection
            const selection = this.getSavedSelection();
            this.updateDisplay(selection);

            // Auto-select vehicle sublocation
            if (selection.vehicle && selection.vehicle.id) {
                this.locationManager.filterLocationsBySalesRep(selection.vehicle.id);
                this.locationManager.autoSelectLocation(selection.vehicle.id);
            }

            // Filter customers by route
            if (selection.route && !this.customerManager.salesRepCustomersLoaded) {
                setTimeout(() => {
                    this.customerManager.filterCustomersByRoute(selection);
                }, 500);
            }
        }

        this.setupEventListeners();
    }

    /**
     * Show selection modal
     */
    showSelectionModal(assignments) {
        // Check if modal exists
        let modal = document.getElementById('salesRepSelectionModal');

        if (!modal) {
            console.error('Sales Rep Selection Modal not found in DOM');
            return;
        }

        // Populate modal with assignments
        this.populateSelectionModal(assignments);

        // Show the modal
        const bsModal = new bootstrap.Modal(modal, {
            backdrop: 'static',
            keyboard: false
        });
        bsModal.show();

        console.log('📋 Sales rep selection modal displayed');
    }

    /**
     * Populate selection modal
     */
    populateSelectionModal(assignments) {
        const vehicleSelect = document.getElementById('salesRepVehicleSelect');
        const routeSelect = document.getElementById('salesRepRouteSelect');

        if (!vehicleSelect || !routeSelect) {
            console.error('Selection modal elements not found');
            return;
        }

        // Clear and populate vehicle select
        vehicleSelect.innerHTML = '<option value="">Select Vehicle</option>';
        assignments.vehicles.forEach(vehicle => {
            const option = document.createElement('option');
            option.value = vehicle.id;
            option.textContent = `${vehicle.name} (${vehicle.vehicle_number || 'N/A'})`;
            option.setAttribute('data-vehicle', JSON.stringify(vehicle));
            vehicleSelect.appendChild(option);
        });

        // Clear and populate route select
        routeSelect.innerHTML = '<option value="">Select Route</option>';
        assignments.routes.forEach(route => {
            const option = document.createElement('option');
            option.value = route.id;
            option.textContent = route.name;
            option.setAttribute('data-route', JSON.stringify(route));
            routeSelect.appendChild(option);
        });

        // Setup confirmation handler
        this.setupModalConfirmHandler();
    }

    /**
     * Setup modal confirm handler
     */
    setupModalConfirmHandler() {
        const confirmBtn = document.getElementById('confirmSalesRepSelection');

        if (!confirmBtn) return;

        // Remove existing listeners
        const newBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newBtn, confirmBtn);

        newBtn.addEventListener('click', () => {
            const vehicleSelect = document.getElementById('salesRepVehicleSelect');
            const routeSelect = document.getElementById('salesRepRouteSelect');

            const selectedVehicleOption = vehicleSelect.options[vehicleSelect.selectedIndex];
            const selectedRouteOption = routeSelect.options[routeSelect.selectedIndex];

            if (!selectedVehicleOption.value || !selectedRouteOption.value) {
                toastr.warning('Please select both vehicle and route', 'Selection Required');
                return;
            }

            const vehicle = JSON.parse(selectedVehicleOption.getAttribute('data-vehicle'));
            const route = JSON.parse(selectedRouteOption.getAttribute('data-route'));

            const selection = {
                vehicle: vehicle,
                route: route,
                canSell: true,
                userId: window.authUserId || null
            };

            // Save selection
            this.saveSelection(selection);
            this.currentSelection = selection;

            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('salesRepSelectionModal'));
            if (modal) modal.hide();

            // Update display
            this.updateDisplay(selection);

            // Filter locations by vehicle
            this.locationManager.filterLocationsBySalesRep(vehicle.id);
            this.locationManager.autoSelectLocation(vehicle.id);

            // Filter customers by route
            setTimeout(() => {
                this.customerManager.filterCustomersByRoute(selection);
            }, 500);

            // Dispatch event
            window.dispatchEvent(new CustomEvent('salesRepSelectionConfirmed', {
                detail: { selection }
            }));

            toastr.success(`Selected: ${vehicle.name} - ${route.name}`, 'Selection Confirmed');
        });
    }

    /**
     * Update sales rep display
     */
    updateDisplay(selection) {
        if (!selection || !selection.vehicle || !selection.route) return;

        // Update desktop display
        this.updateDesktopDisplay(selection);

        // Update mobile display
        this.updateMobileDisplay(selection);

        console.log('✅ Sales rep display updated');
    }

    /**
     * Update desktop display
     */
    updateDesktopDisplay(selection) {
        const display = document.getElementById('salesRepDisplay');
        const vehicleDisplay = document.getElementById('selectedVehicleDisplay');
        const routeDisplay = document.getElementById('selectedRouteDisplay');
        const accessBadge = document.getElementById('salesAccessBadge');

        if (!display || !vehicleDisplay || !routeDisplay) return;

        vehicleDisplay.textContent = `${selection.vehicle.name} (${selection.vehicle.vehicle_number || 'N/A'})`;
        routeDisplay.textContent = selection.route.name;

        if (accessBadge) {
            if (selection.canSell) {
                accessBadge.className = 'badge bg-success';
                accessBadge.textContent = 'Sales Allowed';
            } else {
                accessBadge.className = 'badge bg-warning';
                accessBadge.textContent = 'View Only';
            }
        }

        display.style.display = 'flex';
        display.classList.add('d-flex', 'sales-rep-visible');
        display.classList.remove('d-none');
    }

    /**
     * Update mobile display
     */
    updateMobileDisplay(selection) {
        const display = document.getElementById('salesRepDisplayMenu');
        const vehicleDisplay = document.getElementById('selectedVehicleDisplayMenu');
        const routeDisplay = document.getElementById('selectedRouteDisplayMenu');
        const accessBadge = document.getElementById('salesAccessBadgeMenu');

        if (!display || !vehicleDisplay || !routeDisplay) return;

        vehicleDisplay.textContent = `${selection.vehicle.name} (${selection.vehicle.vehicle_number || 'N/A'})`;
        routeDisplay.textContent = selection.route.name;

        if (accessBadge) {
            if (selection.canSell) {
                accessBadge.className = 'badge bg-success';
                accessBadge.textContent = 'Sales Allowed';
            } else {
                accessBadge.className = 'badge bg-warning';
                accessBadge.textContent = 'View Only';
            }
        }

        display.style.display = 'block';
        display.style.visibility = 'visible';
    }

    /**
     * Hide sales rep display
     */
    hideSalesRepDisplay() {
        const desktopDisplay = document.getElementById('salesRepDisplay');
        const mobileDisplay = document.getElementById('salesRepDisplayMenu');

        if (desktopDisplay) {
            desktopDisplay.style.display = 'none';
            desktopDisplay.classList.add('d-none');
        }

        if (mobileDisplay) {
            mobileDisplay.style.display = 'none';
        }
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Change selection button (Desktop)
        const changeBtn = document.getElementById('changeSalesRepSelection');
        if (changeBtn) {
            changeBtn.addEventListener('click', () => {
                if (this.assignments) {
                    this.showSelectionModal(this.assignments);
                }
            });
        }

        // Change selection button (Mobile)
        const changeBtnMenu = document.getElementById('changeSalesRepSelectionMenu');
        if (changeBtnMenu) {
            changeBtnMenu.addEventListener('click', () => {
                if (this.assignments) {
                    this.showSelectionModal(this.assignments);
                }
            });
        }
    }

    /**
     * Get saved selection
     */
    getSavedSelection() {
        try {
            const data = localStorage.getItem('salesRepSelection');
            if (data) {
                return JSON.parse(data);
            }
        } catch (e) {
            console.error('Error reading saved selection:', e);
        }
        return null;
    }

    /**
     * Save selection
     */
    saveSelection(selection) {
        try {
            localStorage.setItem('salesRepSelection', JSON.stringify(selection));
            console.log('💾 Sales rep selection saved');
        } catch (e) {
            console.error('Error saving selection:', e);
        }
    }

    /**
     * Clear selection
     */
    clearSelection() {
        try {
            localStorage.removeItem('salesRepSelection');
            this.currentSelection = null;
            console.log('🗑️ Sales rep selection cleared');
        } catch (e) {
            console.error('Error clearing selection:', e);
        }
    }

    /**
     * Check if has valid selection
     */
    hasValidSelection() {
        const selection = this.getSavedSelection();
        return selection &&
            selection.vehicle &&
            selection.vehicle.id &&
            selection.route &&
            selection.route.id;
    }

    /**
     * Restrict location access
     */
    restrictLocationAccess(selection) {
        if (!selection || !selection.vehicle) return;

        console.log('🚀 Restricting location access for vehicle:', selection.vehicle.id);

        // Filter and auto-select location
        this.locationManager.filterLocationsBySalesRep(selection.vehicle.id);
        setTimeout(() => {
            this.locationManager.autoSelectLocation(selection.vehicle.id);
        }, 500);
    }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = POSSalesRepManager;
} else {
    window.POSSalesRepManager = POSSalesRepManager;
}
