<!-- Sales Rep Vehicle and Route Selection Modal -->
<div class="modal fade" id="salesRepSelectionModal" tabindex="-1" aria-labelledby="salesRepSelectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="salesRepSelectionModalLabel">
                    <i class="fas fa-truck me-2"></i>Select Vehicle and Route
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    Please select your assigned vehicle and route to proceed with sales.
                </div>

                <!-- Vehicle Selection -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-truck me-2"></i>Vehicle Selection</h6>
                    </div>
                    <div class="card-body">
                        <div class="row" id="vehicleSelectionContainer">
                            <!-- Vehicle options will be populated here -->
                        </div>
                    </div>
                </div>

                <!-- Route Selection -->
                <div class="card" id="routeSelectionCard" style="display: none;">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-route me-2"></i>Route Selection</h6>
                    </div>
                    <div class="card-body">
                        <div class="row" id="routeSelectionContainer">
                            <!-- Route options will be populated here -->
                        </div>
                    </div>
                </div>

                <!-- Selected Information -->
                <div class="card mt-3" id="selectionSummary" style="display: none;">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="fas fa-check me-2"></i>Selection Summary</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Vehicle:</strong> <span id="selectedVehicleName">-</span><br>
                                <small class="text-muted">Number: <span id="selectedVehicleNumber">-</span></small>
                            </div>
                            <div class="col-md-6">
                                <strong>Route:</strong> <span id="selectedRouteName">-</span><br>
                                <small class="text-muted">Cities: <span id="selectedRouteCities">-</span></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" id="confirmSelectionBtn" disabled>
                    <i class="fas fa-check me-2"></i>Confirm Selection
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.vehicle-option, .route-option {
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.vehicle-option:hover, .route-option:hover {
    border-color: #007bff;
    transform: translateY(-2px);
}

.vehicle-option.selected, .route-option.selected {
    border-color: #28a745;
    background-color: #f8f9fa;
}

.vehicle-option .card-body, .route-option .card-body {
    padding: 1rem;
}

.access-badge {
    font-size: 0.75em;
    padding: 0.25rem 0.5rem;
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
            vehicleCard.className = 'col-md-6 mb-3';
            vehicleCard.innerHTML = `
                <div class="card vehicle-option" data-vehicle-id="${sublocation.id}">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="fas fa-truck me-2"></i>${sublocation.name}
                        </h6>
                        <p class="card-text">
                            <small class="text-muted">
                                Vehicle: ${sublocation.vehicle_number || 'N/A'}<br>
                                Type: ${sublocation.vehicle_type || 'N/A'}<br>
                                Routes: ${activeRoutes.length} active
                            </small>
                        </p>
                        <div class="mt-2">
                            ${activeRoutes.map(route => `
                                <span class="badge ${route.can_sell ? 'bg-success' : 'bg-warning'} me-1">
                                    ${route.name} ${route.can_sell ? '(Sell)' : '(View)'}
                                </span>
                            `).join('')}
                        </div>
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
            container.innerHTML = '<div class="col-12"><div class="alert alert-warning">No active routes found for this vehicle.</div></div>';
            return;
        }

        activeRoutes.forEach(route => {
            const routeCard = document.createElement('div');
            routeCard.className = 'col-md-6 mb-3';
            routeCard.innerHTML = `
                <div class="card route-option" data-route-id="${route.id}">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="fas fa-route me-2"></i>${route.name}
                            <span class="badge ${route.can_sell ? 'bg-success' : 'bg-warning'} access-badge ms-2">
                                ${route.can_sell ? 'Sales Allowed' : 'View Only'}
                            </span>
                        </h6>
                        <p class="card-text">
                            <small class="text-muted">
                                Status: ${route.status}<br>
                                Cities: ${route.cities?.map(city => city.name).join(', ') || 'N/A'}
                            </small>
                        </p>
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
            document.getElementById('selectedRouteCities').textContent = 
                selectedRoute.cities?.map(city => city.name).join(', ') || 'N/A';
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
