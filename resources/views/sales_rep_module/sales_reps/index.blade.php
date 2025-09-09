@extends('layout.layout')
@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Sales Representatives</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item">Sales Reps</li>
                                <li class="breadcrumb-item active">Assignments</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Row -->
            <div class="row">
                <div class="col-sm-12">
                    <div class="card card-table">
                        <div class="card-body">
                            <div class="page-header">
                                <div class="row align-items-center">
                                    <div class="col-auto text-end float-end ms-auto download-grp">
                                        <button type="button" class="btn btn-outline-info" id="addSalesRepButton">
                                            Assign New <i class="fas fa-plus px-2"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- DataTable -->
                            <div class="table-responsive">
                                <table id="salesRepsTable" class="datatable table table-striped" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>User ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Locations & Routes</th>
                                            <th>Total Assignments</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dynamic Assignment Modal -->
        <div class="modal fade" id="addAndEditSalesRepModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 id="modalTitle" class="modal-title">
                            <i class="fas fa-user-tie me-2"></i>Assign Routes to Sales Rep
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="salesRepAddUpdateForm">
                            @csrf
                            <input type="hidden" name="id" id="sales_rep_id">

                            <!-- User Selection Row -->
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <label for="user_id" class="form-label fw-bold">
                                        <i class="fas fa-user me-2"></i>Select User <span class="text-danger">*</span>
                                    </label>
                                    <select name="user_id" id="user_id" class="form-control form-control-lg" required>
                                        <option value="">Choose a user...</option>
                                    </select>
                                    <span class="text-danger" id="user_id_error"></span>
                                </div>
                            </div>

                            <!-- Assignment Rows Container -->
                            <div id="assignmentsContainer" style="display: none;">
                                <h6 class="mb-3">
                                    <i class="fas fa-tasks me-2"></i>
                                    Route Assignments for Selected User
                                </h6>
                                <div id="assignmentRows">
                                    <!-- Dynamic rows will be added here -->
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="submit" id="saveBtn" class="btn btn-outline-primary">
                                    <i class="fas fa-save me-2"></i>Save All Assignments
                                </button>
                                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-2"></i>Close
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assignment Row Template (Single Row Only) -->
        <template id="assignmentRowTemplate">
            <div class="assignment-row border rounded p-3 mb-3" data-row-id="" style="background-color: #f8f9fa;">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">
                            <i class="fas fa-map-marker-alt me-2"></i>Sub Location <span class="text-danger">*</span>
                        </label>
                        <select class="form-control sub-location-select" name="assignments[ROWID][sub_location_id]" required>
                            <option value="">Choose location...</option>
                        </select>
                        <small class="text-muted">Vehicle info will be shown from selected location</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">
                            <i class="fas fa-route me-2"></i>Routes (Multiple) <span class="text-danger">*</span>
                        </label>
                        <select class="form-control routes-select" name="assignments[ROWID][route_ids][]" multiple="multiple" required>
                            <option value="">Select routes...</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">
                            <i class="fas fa-shopping-cart me-2"></i>Can Sell
                        </label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="assignments[ROWID][can_sell]" value="1" checked>
                            <label class="form-check-label">Yes</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">
                            <i class="fas fa-calendar-alt me-2"></i>Assign Date
                        </label>
                        <input type="date" class="form-control" name="assignments[ROWID][assigned_date]" 
                               value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                     <div class="col-md-2">
                        <label class="form-label">
                            <i class="fas fa-calendar-times me-2"></i>End Date (Optional)
                        </label>
                        <input type="date" class="form-control" name="assignments[ROWID][end_date]">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-success btn-sm add-row-btn">
                                <i class="fas fa-plus me-2"></i>Add Row
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm remove-row-btn" style="display: none;">
                                <i class="fas fa-trash me-2"></i>Remove Row
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Delete Modal -->
        <div id="deleteModal" class="modal custom-modal fade" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="form-header">
                            <h3>Delete Route Assignment</h3>
                            <p>Are you sure you want to delete this route assignment?</p>
                        </div>
                        <div class="modal-btn delete-action">
                            <div class="row">
                                <input type="hidden" id="delete_sales_rep_id">
                                <div class="col-6">
                                    <button type="button" class="btn btn-primary confirm-delete-btn"
                                        style="width: 100%;">Delete</button>
                                </div>
                                <div class="col-6">
                                    <a href="#" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

   
    
    <style>
        .assignment-row {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 15px;
            padding: 15px;
            background-color: #f8f9fa;
        }
        .user-info-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
        }
        .select2-container {
            width: 100% !important;
        }
        .select2-container--default .select2-selection--multiple {
            min-height: 38px;
        }
        
        /* Grouped view styles */
        .routes-container {
            max-height: 200px;
            overflow-y: auto;
        }
        .route-item {
            background: rgba(255, 255, 255, 0.8);
            border-radius: 4px;
            border-left: 3px solid #007bff;
        }
        .location-group {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .badge.badge-sm {
            font-size: 0.7em;
        }
        .btn-group-vertical .btn {
            border-radius: 4px !important;
            margin-bottom: 2px;
        }
        
        /* DataTable row spacing */
        #salesRepsTable tbody tr {
            border-bottom: 2px solid #dee2e6;
        }
        #salesRepsTable tbody td {
            vertical-align: top;
            padding: 15px 8px;
        }
    </style>



    <script>
        let rowCounter = 0;
        let locationsData = {};
        let routesData = []; // Initialize as array instead of object
        let usersData = {};

        $(document).ready(function() {
            // Load DataTable
            loadDataTable();
            
            // Update date time every minute
            updateDateTime();
            setInterval(updateDateTime, 60000);
        });

        function updateDateTime() {
            const now = new Date();
            const formatted = now.toISOString().slice(0, 19).replace('T', ' ');
            $('#currentDateTime').text(formatted);
        }

        function loadDataTable() {
            if ($.fn.DataTable.isDataTable('#salesRepsTable')) {
                $('#salesRepsTable').DataTable().destroy();
            }

            const table = $('#salesRepsTable').DataTable({
                processing: false,
                serverSide: false,
                ajax: {
                    url: "{{ url('/api/sales-reps') }}",
                    type: "GET",
                    dataSrc: function(res) {
                        console.log('Sales Reps API Response:', res);
                        if (!res.status) {
                            console.log('Failed to load sales reps:', res.message);
                            return [];
                        }
                        
                        // Group data by user
                        const groupedData = {};
                        (res.data || []).forEach(assignment => {
                            const userId = assignment.user_id;
                            if (!groupedData[userId]) {
                                groupedData[userId] = {
                                    user_id: userId,
                                    user: assignment.user,
                                    assignments: [],
                                    locations: new Map(),
                                    routes: new Map()
                                };
                            }
                            
                            // Add this assignment
                            groupedData[userId].assignments.push(assignment);
                            
                            // Track unique locations
                            if (assignment.sub_location) {
                                groupedData[userId].locations.set(assignment.sub_location.id, assignment.sub_location);
                            }
                            
                            // Track unique routes
                            if (assignment.route) {
                                groupedData[userId].routes.set(assignment.route.id, assignment.route);
                            }
                        });
                        
                        // Convert to array format
                        return Object.values(groupedData).map(group => ({
                            user_id: group.user_id,
                            user: group.user,
                            assignments: group.assignments,
                            locations: Array.from(group.locations.values()),
                            routes: Array.from(group.routes.values()),
                            total_assignments: group.assignments.length,
                            status: group.assignments.some(a => a.status === 'active') ? 'active' : 'inactive'
                        }));
                    },
                    error: function(xhr) {
                        console.error('DataTable Ajax Error:', xhr);
                        // Don't show toastr error, let table show "No data available"
                        return [];
                    }
                },
                language: {
                    emptyTable: "No sales representatives found",
                    zeroRecords: "No sales representatives found",
                    loadingRecords: "",
                    processing: ""
                },
                columns: [
                    { 
                        data: 'user_id',
                        render: (data) => data || '—'
                    },
                    { 
                        data: null,
                        render: function(data) {
                            if (data.user && data.user.user_name) {
                                return `<strong>${data.user.user_name}</strong><br><small class="text-muted">${data.user.full_name || ''}</small>`;
                            }
                            return '—';
                        }
                    },
                    { 
                        data: null,
                        render: function(data) {
                            if (data.user && data.user.email) {
                                return data.user.email;
                            }
                            return '—';
                        }
                    },
                    { 
                        data: null,
                        render: function(data) {
                            let html = '';

                            // Group assignments by sub-location
                            const locationRoutes = {};
                            data.assignments.forEach(assignment => {
                                const locId = assignment.sub_location?.id;
                                if (!locationRoutes[locId]) {
                                    locationRoutes[locId] = {
                                        location: assignment.sub_location,
                                        routes: [],
                                        vehicle_number: assignment.sub_location_vehicle_number || null,
                                        vehicle_type: assignment.sub_location_vehicle_type || null
                                    };
                                }
                                if (assignment.route) {
                                    locationRoutes[locId].routes.push({
                                        ...assignment.route,
                                        assigned_date: assignment.assigned_date,
                                        end_date: assignment.end_date,
                                        assignment_status: assignment.status,
                                        can_sell: assignment.can_sell
                                    });
                                }
                            });

                            Object.values(locationRoutes).forEach(locData => {
                                if (locData.location) {
                                    const vehicleNumber = locData.vehicle_number;
                                    const vehicleType = locData.vehicle_type;

                                    html += `
                                        <div class="mb-3 p-2 border rounded bg-light">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <strong class="text-primary">
                                                    <i class="fas fa-map-marker-alt me-1"></i>
                                                    ${locData.location.full_name || locData.location.name}
                                                    ${vehicleNumber ? ` (<span class="text-info">${vehicleNumber}</span>)` : ''}
                                                    ${vehicleType ? ` <span class="badge bg-secondary ms-1">${vehicleType}</span>` : ''}
                                                </strong>
                                                <small class="text-muted">ID: ${locData.location.id}</small>
                                            </div>
                                            <div class="routes-container">
                                    `;

                                    locData.routes.forEach(route => {
                                        const statusColor = route.assignment_status === 'active' ? 'success' : 'secondary';
                                        const sellBadge = route.can_sell ?
                                            '<span class="badge badge-sm bg-success ms-1">Can Sell</span>' :
                                            '<span class="badge badge-sm bg-warning ms-1">No Sell</span>';

                                        html += `
                                            <div class="route-item d-flex justify-content-between align-items-center mb-1 p-1">
                                                <div>
                                                    <span class="badge bg-secondary me-1">${route.name}</span>
                                                    <span class="badge bg-${statusColor} me-1">${route.assignment_status}</span>
                                                    ${sellBadge}
                                                </div>
                                                <div class="text-end">
                                                    <small class="text-muted d-block">
                                                        From: ${new Date(route.assigned_date).toLocaleDateString()}
                                                    </small>
                                                    ${route.end_date ?
                                                        `<small class="text-muted d-block">To: ${new Date(route.end_date).toLocaleDateString()}</small>` :
                                                        '<small class="text-success d-block">Ongoing</small>'
                                                    }
                                                </div>
                                            </div>
                                        `;
                                    });

                                    html += `
                                            </div>
                                        </div>
                                    `;
                                }
                            });

                            return html || '<span class="text-muted">No Assignments</span>';
                        }
                    },
                    {
                        data: 'total_assignments',
                        render: function(data, type, row) {
                            const activeCount = row.assignments.filter(a => a.status === 'active').length;
                            const inactiveCount = row.assignments.filter(a => a.status === 'inactive').length;
                            
                            return `
                                <div class="text-center">
                                    <div class="badge bg-info fs-6 mb-1">${data} Total</div>
                                    <div>
                                        <small class="badge bg-success">${activeCount} Active</small>
                                        <small class="badge bg-secondary">${inactiveCount} Inactive</small>
                                    </div>
                                </div>
                            `;
                        }
                    },
                    {
                        data: 'status',
                        render: (data) => {
                            const status = data || 'inactive';
                            const color = status === 'active' ? 'success' : 'secondary';
                            return `<span class="badge bg-${color}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: (data) => {
                            const userId = data.user_id || data.user?.id || 'unknown';
                            
                            return `
                                <div class="btn-group-vertical" role="group">
                                    <button class='btn btn-sm btn-success addRouteBtn mb-1' 
                                        data-user='${userId}' 
                                        title='Assign New Route'>
                                        <i class="fas fa-plus"></i> Add Route
                                    </button>
                                    <button class='btn btn-sm btn-info viewDetailsBtn mb-1' 
                                        data-user='${userId}' 
                                        title='View All Assignments'>
                                        <i class="fas fa-eye"></i> View Details
                                    </button>
                                    <button class='btn btn-sm btn-warning editUserBtn' 
                                        data-user='${userId}' 
                                        title='Manage User Assignments'>
                                        <i class="fas fa-edit"></i> Manage
                                    </button>
                                </div>
                            `;
                        }
                    }
                ],
                order: [[0, 'asc']], // Order by user_id ascending
                pageLength: 10,
                responsive: true
            });
        }

        // Load all dropdown data
        function loadDropdownData() {
            return new Promise((resolve, reject) => {
                let loadedCount = 0;
                const totalLoads = 3; // Users, Locations, Routes
                
                function checkComplete() {
                    loadedCount++;
                    if (loadedCount >= totalLoads) {
                        resolve();
                    }
                }
                
                // Load Users
                $.ajax({
                    url: '/user-get-all',
                    method: 'GET',
                    success: function(res) {
                        $('#user_id').empty().append('<option value="">Select User</option>');
                        if (res.message && Array.isArray(res.message)) {
                            // Store users data for later use
                            usersData = {};
                            res.message
                                .filter(u => u.role_key === 'sales_rep')
                                .forEach(u => {
                                    usersData[u.id] = u;
                                    $('#user_id').append(
                                        `<option value="${u.id}">${u.user_name} (${u.email}) ==> ${u.role_key}</option>`
                                    );
                                });
                        }
                        checkComplete();
                    },
                    error: function() {
                        toastr.error('Failed to load users.');
                        checkComplete();
                    }
                });

                // Load Locations
                $.ajax({
                    url: '/location-get-all',
                    method: 'GET',
                    success: function(res) {
                        console.log('Locations API Response:', res);
                        if (res.status && Array.isArray(res.data)) {
                            locationsData = {};
                            res.data.forEach(loc => {
                                locationsData[loc.id] = loc;
                                // Also include children locations
                                if (loc.children && Array.isArray(loc.children)) {
                                    loc.children.forEach(child => {
                                        locationsData[child.id] = child;
                                    });
                                }
                            });
                        }
                        checkComplete();
                    },
                    error: (xhr) => {
                        console.error('Failed to load locations:', xhr);
                        toastr.error('Failed to load locations.');
                        checkComplete();
                    }
                });

                // Load Routes
                $.ajax({
                    url: '/api/routes',
                    method: 'GET',
                    success: function(res) {
                        console.log('Routes API Response:', res);
                        if (res.status && Array.isArray(res.data)) {
                            routesData = res.data.filter(r => r.status === 'active');
                        }
                        checkComplete();
                    },
                    error: (xhr) => {
                        console.error('Failed to load routes:', xhr);
                        toastr.error('Failed to load routes.');
                        checkComplete();
                    }
                });
            });
        }

        // Show vehicle info when sub-location changes
        $(document).on('change', '.sub-location-select', function() {
            const locationId = $(this).val();
            const rowDiv = $(this).closest('.assignment-row');
            let vehicleInfoHtml = '';
            if (locationId && locationsData[locationId]) {
                const loc = locationsData[locationId];
                if (loc.vehicle_number || loc.vehicle_type) {
                    vehicleInfoHtml = `
                        <div class="mt-2">
                            <span class="badge bg-info me-2">
                                <i class="fas fa-car me-1"></i>
                                ${loc.vehicle_number ? 'Number: ' + loc.vehicle_number : ''}
                            </span>
                            <span class="badge bg-secondary">
                                <i class="fas fa-truck me-1"></i>
                                ${loc.vehicle_type ? 'Type: ' + loc.vehicle_type : ''}
                            </span>
                        </div>
                    `;
                }
            }
            // Remove any previous vehicle info and add new
            rowDiv.find('.vehicle-info-row').remove();
            if (vehicleInfoHtml) {
                rowDiv.find('.sub-location-select').parent().append(
                    `<div class="vehicle-info-row">${vehicleInfoHtml}</div>`
                );
            }
        });

        // User selection change
        $('#user_id').on('change', function() {
            const selectedOption = $(this).find('option:selected');
            const userId = selectedOption.val();
            
            if (userId) {
                // Show user info
                $('#selectedUserName').text(selectedOption.data('name'));
                $('#selectedUserEmail').text(selectedOption.data('email'));
                $('#selectedUserRole').text(selectedOption.data('role'));
                $('#userInfoContainer').show();
                $('#assignmentsContainer').show();
                
                // Clear existing rows and add first row
                $('#assignmentRows').empty();
                addAssignmentRow();
            } else {
                $('#userInfoContainer').hide();
                $('#assignmentsContainer').hide();
                $('#assignmentRows').empty();
            }
        });

        // Add assignment row
        function addAssignmentRow() {
            rowCounter++;
            const template = document.getElementById('assignmentRowTemplate');
            let clone = template.content.cloneNode(true);
            
            // Replace ROWID placeholder with actual row counter
            const rowHtml = clone.querySelector('.assignment-row').outerHTML.replace(/ROWID/g, rowCounter);
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = rowHtml;
            const rowDiv = tempDiv.querySelector('.assignment-row');
            
            // Set unique row ID
            rowDiv.setAttribute('data-row-id', rowCounter);
            
            // Add to container
            document.getElementById('assignmentRows').appendChild(rowDiv);
            
            // Populate locations dropdown (only sub-locations with parent_id)
            const locationSelect = $(`[data-row-id="${rowCounter}"] .sub-location-select`);
            if (locationsData && typeof locationsData === 'object') {
                Object.values(locationsData).forEach(loc => {
                    if (loc.parent_id) { // Only sub-locations
                        locationSelect.append(`<option value="${loc.id}">${loc.name}</option>`);
                    }
                });
            } else {
                console.warn('Locations data not loaded yet:', locationsData);
            }
            
            // Initialize Select2 for routes
            $(`[data-row-id="${rowCounter}"] .routes-select`).select2({
                placeholder: "Select multiple routes...",
                allowClear: true
            });
            
            // Populate all routes initially
            const routesSelect = $(`[data-row-id="${rowCounter}"] .routes-select`);
            if (Array.isArray(routesData) && routesData.length > 0) {
                routesData.forEach(route => {
                    const option = new Option(route.name, route.id, false, false);
                    routesSelect.append(option);
                });
            } else {
                console.warn('Routes data not loaded yet or is empty:', routesData);
                // Optionally load routes data if not available
                $.ajax({
                    url: '/api/routes',
                    method: 'GET',
                    success: function(res) {
                        console.log('Routes loaded in addAssignmentRow:', res);
                        if (res.status && Array.isArray(res.data)) {
                            routesData = res.data.filter(r => r.status === 'active');
                            routesData.forEach(route => {
                                const option = new Option(route.name, route.id, false, false);
                                routesSelect.append(option);
                            });
                            routesSelect.trigger('change');
                        }
                    },
                    error: (xhr) => {
                        console.error('Failed to load routes in addAssignmentRow:', xhr);
                        toastr.error('Failed to load routes.');
                    }
                });
            }
            routesSelect.trigger('change');
            
            // Update remove button visibility
            updateRemoveButtons();
            
            // Add event listeners for this row
            bindRowEvents(rowCounter);
        }

        // Bind events for a specific row
        function bindRowEvents(rowId) {
            const rowSelector = `[data-row-id="${rowId}"]`;
            
            // Add row button
            $(rowSelector + ' .add-row-btn').on('click', function() {
                addAssignmentRow();
            });
            
            // Remove row button
            $(rowSelector + ' .remove-row-btn').on('click', function() {
                $(rowSelector).remove();
                updateRemoveButtons();
            });
            
            // Sub location change - No filtering needed since routes don't have location_id
            $(rowSelector + ' .sub-location-select').on('change', function() {
                const locationId = $(this).val();
                console.log('Selected location:', locationId, locationsData[locationId]);
            });
        }

        // Update remove button visibility
        function updateRemoveButtons() {
            const rows = $('.assignment-row');
            rows.each(function(index) {
                const removeBtn = $(this).find('.remove-row-btn');
                if (rows.length > 1) {
                    removeBtn.show();
                } else {
                    removeBtn.hide();
                }
            });
        }

        // Add new assignment
        $('#addSalesRepButton').on('click', function() {
            $('#salesRepAddUpdateForm')[0].reset();
            $('#sales_rep_id').val('');
            $('#user_id').prop('disabled', false);
            
            $('#modalTitle').html('<i class="fas fa-user-tie me-2"></i>Assign Routes to Sales Rep');
            $('#saveBtn').html('<i class="fas fa-save me-2"></i>Save All Assignments');
            $('.text-danger').text('');
            
            $('#userInfoContainer').hide();
            $('#assignmentsContainer').hide();
            $('#assignmentRows').empty();
            rowCounter = 0;
            
            loadDropdownData();
            $('#addAndEditSalesRepModal').modal('show');
        });

        // Save form
        $('#salesRepAddUpdateForm').on('submit', function(e) {
            e.preventDefault();

            const userId = $('#user_id').val();
            
            if (!userId) {
                toastr.error('Please select a user');
                return;
            }

            const assignments = [];
            $('.assignment-row').each(function() {
                const row = $(this);
                const subLocationId = row.find('.sub-location-select').val();
                const routeIds = row.find('.routes-select').val();
                const canSell = row.find('input[name*="can_sell"]').is(':checked');
                const assignedDate = row.find('input[name*="assigned_date"]').val();
                const endDate = row.find('input[name*="end_date"]').val();
                const status = row.find('select[name*="status"]').val();

                if (subLocationId && routeIds && routeIds.length > 0) {
                    assignments.push({
                        sub_location_id: subLocationId,
                        route_ids: routeIds,
                        can_sell: canSell,
                        assigned_date: assignedDate,
                        end_date: endDate || null,
                        status: status
                    });
                }
            });

            if (assignments.length === 0) {
                toastr.error('Please fill in at least one complete assignment');
                return;
            }

            const formData = {
                user_id: userId,
                assignments: assignments,
                _token: $('meta[name="csrf-token"]').attr('content')
            };

            console.log('Submitting form data:', formData);

            $.ajax({
                url: '/api/sales-reps',
                method: 'POST',
                data: formData,
                success: function(res) {
                    console.log('Success response:', res);
                    $('#addAndEditSalesRepModal').modal('hide');
                    loadDataTable();
                    toastr.success(res.message || `Successfully created ${assignments.length} assignment(s)!`);
                },
                error: function(xhr) {
                    console.error('Error response:', xhr);
                    const errors = xhr.responseJSON?.errors || {};
                    $('.text-danger').text('');
                    Object.keys(errors).forEach(key => {
                        $(`#${key}_error`).text(Array.isArray(errors[key]) ? errors[key][0] : errors[key]);
                    });
                    toastr.error(xhr.responseJSON?.message || 'An error occurred.');
                }
            });
        });

        // Edit assignment
        $('#salesRepsTable').on('click', '.editBtn', function() {
            const id = $(this).data('id');
            console.log('Editing assignment ID:', id);
            
            // For now, just open the add modal
            $('#addSalesRepButton').click();
            toastr.info('Edit functionality will be implemented based on your API structure');
        });

        // Assign another route
        $('#salesRepsTable').on('click', '.addRouteBtn', function() {
            const userId = $(this).data('user');
            console.log('Adding route for user:', userId);
            
            // Get user data from the table
            const tableData = $('#salesRepsTable').DataTable().data().toArray();
            const userData = tableData.find(row => row.user_id == userId);
            
            $('#salesRepAddUpdateForm')[0].reset();
            $('#sales_rep_id').val('');
            
            if (userData && userData.user) {
                const user = userData.user;
                $('#selectedUserName').text(user.user_name);
                $('#selectedUserEmail').text(user.email);
                $('#selectedUserRole').text(user.role_key || 'Sales Rep');
                $('#userInfoContainer').show();
                $('#assignmentsContainer').show();
                
                $('#assignmentRows').empty();
                addAssignmentRow();
            }
            
            $('#modalTitle').html('<i class="fas fa-user-tie me-2"></i>Assign Another Route');
            $('#saveBtn').html('<i class="fas fa-save me-2"></i>Save Assignment');
            $('.text-danger').text('');
            
            // Load dropdown data and then set the user
            loadDropdownData().then(() => {
                // Set user after dropdown is loaded
                $('#user_id').val(userId).prop('disabled', true);
                console.log('User selected in dropdown:', userId);
            });
            
            $('#addAndEditSalesRepModal').modal('show');
        });

        // View details for grouped user
        $('#salesRepsTable').on('click', '.viewDetailsBtn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const userId = $(this).data('user');
            console.log('Viewing details for user:', userId);
            
            // Get current table data to find user assignments
            const tableData = $('#salesRepsTable').DataTable().data().toArray();
            const userData = tableData.find(row => row.user_id == userId);
            
            if (userData) {
                showUserDetailsModal(userData, userId);
            }
        });

        // Manage user assignments
        $('#salesRepsTable').on('click', '.editUserBtn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const userId = $(this).data('user');
            console.log('Managing assignments for user:', userId);
            
            // Get current table data to find user assignments
            const tableData = $('#salesRepsTable').DataTable().data().toArray();
            const userData = tableData.find(row => row.user_id == userId);
            
            if (userData) {
                // Call the view details functionality directly without triggering events
                showUserDetailsModal(userData, userId);
            }
        });

        // Extract the modal creation to a separate function to avoid recursion
        function showUserDetailsModal(userData, userId) {
            let detailsHtml = `
                <div class="modal fade" id="userDetailsModal" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title">
                                    <i class="fas fa-user-tie me-2"></i>
                                    ${userData.user.user_name} - Assignment Details
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <strong>User Name:</strong> ${userData.user.user_name}<br>
                                        <strong>Full Name:</strong> ${userData.user.full_name || 'N/A'}<br>
                                        <strong>Email:</strong> ${userData.user.email}
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Total Assignments:</strong> ${userData.total_assignments}<br>
                                        <strong>Overall Status:</strong> 
                                        <span class="badge bg-${userData.status === 'active' ? 'success' : 'secondary'}">
                                            ${userData.status.charAt(0).toUpperCase() + userData.status.slice(1)}
                                        </span>
                                    </div>
                                </div>
                                <hr>
                                <h6><i class="fas fa-list me-2"></i>All Assignments</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>ID</th>
                                                <th>Location</th>
                                                <th>Route</th>
                                                <th>Assigned Date</th>
                                                <th>End Date</th>
                                                <th>Can Sell</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
            `;
            
            userData.assignments.forEach(assignment => {
                detailsHtml += `
                    <tr>
                        <td>${assignment.id}</td>
                        <td>
                            <span class="badge bg-info">${assignment.sub_location?.name || 'N/A'}</span>
                            <small class="d-block text-muted">${assignment.sub_location?.full_name || ''}</small>
                        </td>
                        <td>
                            <span class="badge bg-secondary">${assignment.route?.name || 'N/A'}</span>
                        </td>
                        <td>${new Date(assignment.assigned_date).toLocaleDateString()}</td>
                        <td>${assignment.end_date ? new Date(assignment.end_date).toLocaleDateString() : '<span class="text-success">Ongoing</span>'}</td>
                        <td>
                            <span class="badge bg-${assignment.can_sell ? 'success' : 'warning'}">
                                ${assignment.can_sell ? 'Yes' : 'No'}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-${assignment.status === 'active' ? 'success' : 'secondary'}">
                                ${assignment.status.charAt(0).toUpperCase() + assignment.status.slice(1)}
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-danger deleteAssignmentBtn" 
                                data-id="${assignment.id}" title="Delete Assignment">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            detailsHtml += `
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-success addRouteBtn" data-user="${userId}">
                                <i class="fas fa-plus me-2"></i>Add New Route
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            `;
            
            // Remove existing modal if present
            $('#userDetailsModal').remove();
            
            // Add modal to body and show
            $('body').append(detailsHtml);
            $('#userDetailsModal').modal('show');
            
            // Handle delete assignment within modal
            $('#userDetailsModal').on('click', '.deleteAssignmentBtn', function() {
                const assignmentId = $(this).data('id');
                if (confirm('Are you sure you want to delete this assignment?')) {
                    $.ajax({
                        url: `/api/sales-reps/${assignmentId}`,
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                        success: function(res) {
                            $('#userDetailsModal').modal('hide');
                            loadDataTable();
                            toastr.success(res.message || 'Assignment deleted successfully');
                        },
                        error: function(xhr) {
                            console.error('Delete error:', xhr);
                            toastr.error(xhr.responseJSON?.message || 'Delete failed.');
                        }
                    });
                }
            });
            
            // Handle add route within modal
            $('#userDetailsModal').on('click', '.addRouteBtn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const userId = $(this).data('user');
                $('#userDetailsModal').modal('hide');
                
                // Wait for modal to hide, then trigger the main add route functionality
                setTimeout(function() {
                    // Get user data from the table
                    const tableData = $('#salesRepsTable').DataTable().data().toArray();
                    const userData = tableData.find(row => row.user_id == userId);
                    
                    // Call the add route functionality directly
                    $('#salesRepAddUpdateForm')[0].reset();
                    $('#sales_rep_id').val('');
                    
                    if (userData && userData.user) {
                        const user = userData.user;
                        $('#selectedUserName').text(user.user_name);
                        $('#selectedUserEmail').text(user.email);
                        $('#selectedUserRole').text(user.role_key || 'Sales Rep');
                        $('#userInfoContainer').show();
                        $('#assignmentsContainer').show();
                        
                        $('#assignmentRows').empty();
                        addAssignmentRow();
                    }
                    
                    $('#modalTitle').html('<i class="fas fa-user-tie me-2"></i>Assign Another Route');
                    $('#saveBtn').html('<i class="fas fa-save me-2"></i>Save Assignment');
                    $('.text-danger').text('');
                    
                    // Load dropdown data and then set the user
                    loadDropdownData().then(() => {
                        // Set user after dropdown is loaded
                        $('#user_id').val(userId).prop('disabled', true);
                        console.log('User selected in modal dropdown:', userId);
                    });
                    
                    $('#addAndEditSalesRepModal').modal('show');
                }, 300);
            });
        }

        // Delete
        $('#salesRepsTable').on('click', '.deleteBtn', function() {
            $('#delete_sales_rep_id').val($(this).data('id'));
            $('#deleteModal').modal('show');
        });

        $('.confirm-delete-btn').on('click', function() {
            const id = $('#delete_sales_rep_id').val();
            $.ajax({
                url: `/api/sales-reps/${id}`,
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function(res) {
                    $('#deleteModal').modal('hide');
                    loadDataTable();
                    toastr.success(res.message || 'Sales Representative deleted successfully');
                },
                error: function(xhr) {
                    console.error('Delete error:', xhr);
                    toastr.error(xhr.responseJSON?.message || 'Delete failed.');
                }
            });
        });

    </script>

@endsection