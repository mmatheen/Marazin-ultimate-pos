<!-- Sales Rep Vehicle and Route Selection Modal - REQUIRED SELECTION -->
<div class="modal fade" id="salesRepSelectionModal" tabindex="-1" aria-labelledby="salesRepSelectionModalLabel"
     data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border">
            <div class="modal-header bg-white border-bottom">
                <h5 class="modal-title" id="salesRepSelectionModalLabel">
                    Select Your Vehicle and Route
                </h5>
            </div>
            <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                <div class="alert alert-info mb-4">
                    <strong>Important:</strong> You must select your vehicle and route to continue.
                </div>

                <!-- Vehicle Selection -->
                <div class="mb-4">
                    <h6 class="mb-3 text-dark">Step 1: Select Vehicle</h6>
                    <div id="vehicleSelectionContainer">
                        <!-- Vehicle options will be populated here -->
                    </div>
                </div>

                <!-- Route Selection -->
                <div class="mb-4" id="routeSelectionCard" style="display: none;">
                    <h6 class="mb-3 text-dark">Step 2: Select Route</h6>
                    <div id="routeSelectionContainer">
                        <!-- Route options will be populated here -->
                    </div>
                </div>

                <!-- Selected Information -->
                <div class="mt-4 p-3 bg-light border rounded" id="selectionSummary" style="display: none;">
                    <h6 class="mb-3">Your Selection</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <strong class="d-block text-muted mb-1">Vehicle:</strong>
                            <div id="selectedVehicleName" class="fw-bold">-</div>
                            <small class="text-muted" id="selectedVehicleNumber">-</small>
                        </div>
                        <div class="col-md-6">
                            <strong class="d-block text-muted mb-1">Route:</strong>
                            <div id="selectedRouteName" class="fw-bold">-</div>
                            <small class="text-muted">
                                <span id="selectedRouteCitiesCount">0</span> cities
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top bg-white">
                <button type="button" class="btn btn-primary px-4" id="confirmSelectionBtn" disabled>
                    Confirm and Continue
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Ensure modal appears above all POS elements */
#salesRepSelectionModal {
    z-index: 9999 !important;
}

#salesRepSelectionModal .modal-dialog {
    z-index: 10000 !important;
}

#salesRepSelectionModal .modal-content {
    z-index: 10001 !important;
}

.vehicle-option, .route-option {
    cursor: pointer;
    transition: border-color 0.2s;
    border: 2px solid #dee2e6;
    padding: 15px;
    margin-bottom: 10px;
    border-radius: 4px;
}

.vehicle-option:hover, .route-option:hover {
    border-color: #0d6efd;
}

.vehicle-option.selected, .route-option.selected {
    border-color: #0d6efd;
    background-color: #f8f9fa;
}

#confirmSelectionBtn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.card {
    transition: all 0.3s ease;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let selectedVehicle = null;
    let selectedRoute = null;
    let salesRepAssignments = [];

    // Function to load sales rep assignments
    function loadSalesRepAssignments(forceRefresh = false) {
        // Check if assignments are already loaded globally and not forcing refresh
        if (!forceRefresh && window.salesRepAssignments && window.salesRepAssignments.length > 0) {
            // Check if cached data is from today (to ensure fresh status updates)
            const lastFetch = localStorage.getItem('salesRepAssignmentsFetchTime');
            const today = new Date().toDateString();

            if (lastFetch && new Date(lastFetch).toDateString() === today) {
                salesRepAssignments = window.salesRepAssignments;
                populateVehicleOptions();
                return Promise.resolve(true);
            }
        }

        // Clear cached data and fetch fresh assignments
        window.salesRepAssignments = null;
        localStorage.removeItem('salesRepAssignmentsFetchTime');

        return fetch('/sales-rep/my-assignments', {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                salesRepAssignments = data.data || [];
                window.salesRepAssignments = salesRepAssignments; // Store globally
                localStorage.setItem('salesRepAssignmentsFetchTime', new Date().toISOString());
                populateVehicleOptions();
                return true;
            } else {
                console.error('Failed to load assignments:', data.message);
                return false;
            }
        })
        .catch(error => {
            console.error('Error loading assignments:', error);
            return false;
        });
    }

    // Function to populate vehicle options
    function populateVehicleOptions() {
        const container = document.getElementById('vehicleSelectionContainer');
        container.innerHTML = '';

        if (salesRepAssignments.length === 0) {
            container.innerHTML = '<div class="col-12"><div class="alert alert-warning">No vehicle assignments found.</div></div>';
            return;
        }

        // Group by vehicle (sublocation)
        const vehicleGroups = {};
        salesRepAssignments.forEach(assignment => {
            const sublocationId = assignment.sub_location?.id;
            if (sublocationId && !vehicleGroups[sublocationId]) {
                vehicleGroups[sublocationId] = {
                    sublocation: assignment.sub_location,
                    routes: []
                };
            }
            if (sublocationId && assignment.route) {
                vehicleGroups[sublocationId].routes.push({
                    ...assignment.route,
                    can_sell: assignment.can_sell,
                    status: assignment.status
                });
            }
        });

        Object.values(vehicleGroups).forEach(vehicleGroup => {
            const sublocation = vehicleGroup.sublocation;
            const activeRoutes = vehicleGroup.routes.filter(route => route.status === 'active');

            const vehicleCard = document.createElement('div');
            vehicleCard.className = 'vehicle-option';
            vehicleCard.innerHTML = `
                <div data-vehicle-id="${sublocation.id}">
                    <h6 class="mb-2">
                        <i class="fas fa-truck me-2"></i>${sublocation.name}
                    </h6>
                    <div class="text-muted small mb-2">
                        <div><strong>Location:</strong> ${sublocation.parent?.name || 'Main Location'}</div>
                        <div><strong>Number:</strong> ${sublocation.vehicle_number || 'N/A'}</div>
                        <div><strong>Type:</strong> ${sublocation.vehicle_type || 'Lorry'}</div>
                        <div><strong>Routes:</strong> ${activeRoutes.length} active</div>
                    </div>
                </div>
            `;

            vehicleCard.addEventListener('click', () => selectVehicle(sublocation, vehicleGroup.routes));
            container.appendChild(vehicleCard);
        });
    }

    // Function to select a vehicle
    function selectVehicle(vehicle, routes) {
        selectedVehicle = vehicle;
        selectedRoute = null;

        // Update UI
        document.querySelectorAll('.vehicle-option').forEach(option => {
            option.classList.remove('selected');
        });
        document.querySelector(`[data-vehicle-id="${vehicle.id}"]`).classList.add('selected');

        // Show route selection
        populateRouteOptions(routes);
        document.getElementById('routeSelectionCard').style.display = 'block';

        updateSelectionSummary();
    }

    // Function to populate route options
    function populateRouteOptions(routes) {
        const container = document.getElementById('routeSelectionContainer');
        container.innerHTML = '';

        const activeRoutes = routes.filter(route => route.status === 'active');

        if (activeRoutes.length === 0) {
            container.innerHTML = '<div class="col-12"><div class="alert alert-warning"><i class="fas fa-exclamation-circle me-2"></i>No active routes found for this vehicle.</div></div>';
            return;
        }

        activeRoutes.forEach(route => {
            const cityCount = route.cities?.length || 0;
            const citiesBadges = route.cities?.map(city =>
                `<span class="badge bg-light text-dark border me-1 mb-1">${city.name}</span>`
            ).join('') || '<span class="text-muted">No cities</span>';
            const routeId = 'route-' + route.id;

            const routeCard = document.createElement('div');
            routeCard.className = 'route-option';
            routeCard.innerHTML = `
                <div data-route-id="${route.id}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-route me-2"></i>
                            <strong>${route.name}</strong>
                            ${route.can_sell ? '' : '<small class="text-muted ms-2">(View Only)</small>'}
                        </div>
                        <div>
                            <span class="text-muted me-2">${cityCount} cities</span>
                            <a href="#" class="text-primary text-decoration-none view-cities-link" data-bs-toggle="collapse" data-bs-target="#${routeId}" onclick="event.stopPropagation()">
                                <small>View</small>
                            </a>
                        </div>
                    </div>
                    <div class="collapse mt-2" id="${routeId}">
                        <div class="p-2 bg-light border rounded d-flex flex-wrap">
                            ${citiesBadges}
                        </div>
                    </div>
                </div>
            `;

            routeCard.addEventListener('click', () => selectRoute(route));
            container.appendChild(routeCard);
        });
    }

    // Function to select a route
    function selectRoute(route) {
        selectedRoute = route;

        // Update UI
        document.querySelectorAll('.route-option').forEach(option => {
            option.classList.remove('selected');
        });
        document.querySelector(`[data-route-id="${route.id}"]`).classList.add('selected');

        updateSelectionSummary();
        document.getElementById('confirmSelectionBtn').disabled = false;
    }

    // Function to update selection summary
    function updateSelectionSummary() {
        const summaryDiv = document.getElementById('selectionSummary');

        if (selectedVehicle) {
            document.getElementById('selectedVehicleName').textContent = selectedVehicle.name;
            document.getElementById('selectedVehicleNumber').textContent = selectedVehicle.vehicle_number || 'N/A';
        }

        if (selectedRoute) {
            document.getElementById('selectedRouteName').textContent = selectedRoute.name;
            document.getElementById('selectedRouteCitiesCount').textContent = selectedRoute.cities?.length || 0;
            summaryDiv.style.display = 'block';
        }
    }

    // Confirm selection button
    document.getElementById('confirmSelectionBtn').addEventListener('click', function() {
        if (selectedVehicle && selectedRoute) {
            // Create complete selection object
            const selection = {
                vehicle: selectedVehicle,
                route: selectedRoute,
                canSell: selectedRoute.can_sell
            };

            // Store selection in both session and local storage for persistence
            try {
                const selectionJson = JSON.stringify(selection);
                sessionStorage.setItem('salesRepSelection', selectionJson);
                localStorage.setItem('salesRepSelection', selectionJson);
                console.log('Sales rep selection stored in both session and local storage');
            } catch (e) {
                console.error('Failed to store sales rep selection:', e);
            }

            // Dispatch custom event
            window.dispatchEvent(new CustomEvent('salesRepSelectionConfirmed', {
                detail: selection
            }));

            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('salesRepSelectionModal'));
            modal.hide();

            // Show success message
            if (typeof toastr !== 'undefined') {
                toastr.success(`Selected ${selectedVehicle.name} - ${selectedRoute.name}`, 'Selection Confirmed');
            }
        }
    });

    // Function to show modal
    window.showSalesRepModal = function(forceRefresh = false) {
        loadSalesRepAssignments(forceRefresh).then(success => {
            if (success) {
                const modal = new bootstrap.Modal(document.getElementById('salesRepSelectionModal'));
                modal.show();
            } else {
                if (typeof toastr !== 'undefined') {
                    toastr.error('Failed to load vehicle assignments', 'Error');
                }
            }
        });
    };

    // Function to check if selection exists
    window.hasSalesRepSelection = function() {
        return sessionStorage.getItem('salesRepSelection') !== null;
    };

    // Function to get current selection
    window.getSalesRepSelection = function() {
        try {
            // First check sessionStorage (current session)
            let stored = sessionStorage.getItem('salesRepSelection');
            let parsed = stored ? JSON.parse(stored) : null;

            // If not found in sessionStorage, check localStorage (persistent across refreshes)
            if (!parsed) {
                stored = localStorage.getItem('salesRepSelection');
                parsed = stored ? JSON.parse(stored) : null;

                // If found in localStorage, also store in sessionStorage for current session
                if (parsed) {
                    sessionStorage.setItem('salesRepSelection', JSON.stringify(parsed));
                    console.log('Restored sales rep selection from localStorage to sessionStorage');
                }
            }

            return parsed;
        } catch (e) {
            console.warn('Error parsing sales rep selection from storage:', e);
            return null;
        }
    };

    // Function to clear selection
    window.clearSalesRepSelection = function() {
        sessionStorage.removeItem('salesRepSelection');
        localStorage.removeItem('salesRepSelection');
        console.log('Sales rep selection cleared from both session and local storage');
    };

    // Function to refresh assignments and clear cache
    window.refreshSalesRepAssignments = function() {
        window.salesRepAssignments = null;
        localStorage.removeItem('salesRepAssignmentsFetchTime');

        // Also clear any invalid selections
        const currentSelection = window.getSalesRepSelection();
        if (currentSelection) {
            // Force refresh to check if the selection is still valid
            loadSalesRepAssignments(true).then(success => {
                if (success) {
                    // Check if current selection is still valid (not expired)
                    const isStillValid = salesRepAssignments.some(assignment =>
                        assignment.sub_location?.id === currentSelection.vehicle?.id &&
                        assignment.route?.id === currentSelection.route?.id &&
                        assignment.status === 'active'
                    );

                    if (!isStillValid) {
                        window.clearSalesRepSelection();
                        console.log('Cleared invalid/expired sales rep selection');
                        if (typeof toastr !== 'undefined') {
                            toastr.warning('Your previous vehicle/route selection has expired. Please select again.', 'Selection Expired');
                        }
                    }
                }
            });
        }

        console.log('Sales rep assignments cache refreshed');
    };
});
</script>
