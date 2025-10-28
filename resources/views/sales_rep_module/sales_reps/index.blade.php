@extends('layout.layout')
@section('content')
    <style>
        /* Custom optgroup styling */
        .sub-location-select optgroup {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 600;
            font-size: 0.9em;
            padding: 5px 8px;
        }
        
        .sub-location-select option {
            padding: 8px 12px;
            color: #212529;
        }
        
        .sub-location-select .assigned-location {
            color: #6c757d !important;
            font-style: italic;
        }
        
        .sub-location-select option:hover {
            background-color: #e9ecef;
        }
        
        /* Improve select2 dropdown styling */
        .select2-container--default .select2-results__group {
            background-color: #f1f3f4;
            color: #495057;
            font-weight: 600;
            font-size: 0.85em;
            padding: 8px 12px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .select2-container--default .select2-results__option--highlighted {
            background-color: #007bff !important;
        }
    </style>
    
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
        
        /* Enhanced Status Styles */
        .status-active { border-left: 4px solid #28a745; }
        .status-expired { border-left: 4px solid #dc3545; }
        .status-upcoming { border-left: 4px solid #17a2b8; }
        .status-cancelled { border-left: 4px solid #6c757d; }
        
        /* Expiring soon warning animation */
        .expiring-warning {
            animation: pulse-warning 2s infinite;
            box-shadow: 0 0 10px rgba(255, 193, 7, 0.5);
        }
        
        @keyframes pulse-warning {
            0% { box-shadow: 0 0 5px rgba(255, 193, 7, 0.3); }
            50% { box-shadow: 0 0 15px rgba(255, 193, 7, 0.7); }
            100% { box-shadow: 0 0 5px rgba(255, 193, 7, 0.3); }
        }
        
        /* Status badges with icons */
        .badge-status {
            font-size: 0.75rem;
            padding: 0.375rem 0.5rem;
            border-radius: 0.375rem;
        }
        
        /* Assignment item styling */
        .assignment-item {
            transition: all 0.3s ease;
            border-radius: 8px;
            margin-bottom: 8px;
        }
        
        .assignment-item:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        /* Status summary in header */
        #statusSummary .badge {
            margin: 0 2px;
            font-size: 0.85rem;
            padding: 0.5rem 0.75rem;
        }
        
        /* Enhanced route item styling */
        .route-item {
            border: 1px solid #e9ecef !important;
            background-color: #ffffff;
            transition: all 0.2s ease;
        }
        
        .route-item:hover {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .route-item.border-danger {
            border-left: 4px solid #dc3545 !important;
            background-color: #f8d7da !important;
            border-color: #f5c6cb !important;
        }
        
        .route-item.border-info {
            border-left: 4px solid #0dcaf0 !important;
            background-color: #d1ecf1 !important;
            border-color: #bee5eb !important;
        }
        
        /* Responsive badge wrapping */
        .badge-container {
            display: flex;
            flex-wrap: wrap;
            gap: 2px;
            justify-content: center;
        }
        
        @media (max-width: 768px) {
            .badge-container {
                justify-content: flex-start;
            }
        }
    </style>



    <script>
        let rowCounter = 0;
        let locationsData = {};
        let routesData = []; // Initialize as array instead of object
        let usersData = {};
        let validationErrors = [];

        // Helper function to format date for input fields
        function formatDateForInput(dateString) {
            if (!dateString) return '';
            try {
                const date = new Date(dateString);
                return date.toISOString().split('T')[0]; // Returns YYYY-MM-DD format
            } catch (error) {
                console.error('Date formatting error:', error);
                return '';
            }
        }

        // Reusable edit assignment function
        function editAssignment(assignmentId) {
            console.log('Editing assignment ID:', assignmentId);
            
            // Find the assignment data from the table
            const tableData = $('#salesRepsTable').DataTable().data().toArray();
            let assignmentToEdit = null;
            let userDataForAssignment = null;
            
            // Search through all users and their assignments
            for (let userData of tableData) {
                if (userData.assignments) {
                    const foundAssignment = userData.assignments.find(a => a.id == assignmentId);
                    if (foundAssignment) {
                        assignmentToEdit = foundAssignment;
                        userDataForAssignment = userData;
                        break;
                    }
                }
            }
            
            if (!assignmentToEdit || !userDataForAssignment) {
                toastr.error('Assignment not found.');
                return;
            }
            
            console.log('Found assignment to edit:', assignmentToEdit);
            console.log('User data:', userDataForAssignment.user);
            
            // Reset form
            $('#salesRepAddUpdateForm')[0].reset();
            $('#sales_rep_id').val(assignmentId); // Store assignment ID for update
            rowCounter = 0;
            
            // Set modal for editing mode
            $('#modalTitle').html('<i class="fas fa-edit me-2"></i>Edit Assignment - ' + userDataForAssignment.user.user_name);
            $('#saveBtn').html('<i class="fas fa-save me-2"></i>Update Assignment');
            $('.text-danger').text('');
            
            // Show user information
            const user = userDataForAssignment.user;
            $('#selectedUserName').text(user.user_name);
            $('#selectedUserEmail').text(user.email);
            $('#selectedUserRole').text(user.role_key || 'Sales Rep');
            $('#userInfoContainer').show();
            $('#assignmentsContainer').show();
            
            // Load dropdown data first
            loadDropdownData().then(() => {
                // Set user (disabled for editing)
                $('#user_id').val(userDataForAssignment.user_id).prop('disabled', true);
                
                // Clear and populate with current assignment data
                $('#assignmentRows').empty();
                addAssignmentRow();
                
                setTimeout(() => {
                    // Populate the form with current assignment data
                    const currentRow = $('.assignment-row').first();
                    
                    // Set location
                    currentRow.find('.sub-location-select').val(assignmentToEdit.sub_location_id);
                    
                    // Set routes (single route for edit mode)
                    const routesSelect = currentRow.find('.routes-select');
                    
                    // IMPORTANT: Destroy and recreate Select2 for single selection in edit mode
                    if (routesSelect.hasClass("select2-hidden-accessible")) {
                        routesSelect.select2('destroy');
                    }
                    
                    // Reinitialize Select2 with single selection for edit mode
                    routesSelect.select2({
                        placeholder: "Select single route (Edit Mode)",
                        allowClear: true,
                        multiple: false // Force single selection in edit mode
                    });
                    
                    if (assignmentToEdit.route_id) {
                        routesSelect.val(assignmentToEdit.route_id).trigger('change'); // Single value, not array
                    }
                    
                    // In edit mode, we're editing a single route assignment
                    // Add note to user about this
                    currentRow.find('.routes-select').parent().append(
                        '<small class="text-warning d-block mt-1"><i class="fas fa-exclamation-triangle"></i> <strong>Edit mode:</strong> You can only modify one route per assignment. To assign multiple routes, create separate assignments.</small>'
                    );
                    
                    // Set other fields
                    currentRow.find('input[name*="can_sell"]').prop('checked', assignmentToEdit.can_sell);
                    currentRow.find('input[name*="assigned_date"]').val(formatDateForInput(assignmentToEdit.assigned_date));
                    if (assignmentToEdit.end_date) {
                        currentRow.find('input[name*="end_date"]').val(formatDateForInput(assignmentToEdit.end_date));
                    }
                    currentRow.find('select[name*="status"]').val(assignmentToEdit.status);
                    
                    console.log('Form populated with assignment data (single route mode)');
                    toastr.info('Assignment loaded for editing.');
                }, 300);
            }).catch(error => {
                console.error('Failed to load dropdown data for editing:', error);
                toastr.error('Failed to load form data.');
            });
            
            // Show the modal
            $('#addAndEditSalesRepModal').modal('show');
        }

        $(document).ready(function() {
            // Load DataTable
            loadDataTable();
            
            // Start auto-refresh for status updates
            startAutoRefresh();
            
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
                                        // Enhanced status badge with better colors and icons
                                        let statusBadge = '';
                                        let statusIcon = '';
                                        let statusClass = '';
                                        let warningBadge = '';
                                        
                                        // Debug logging
                                        console.log('Route status:', route.assignment_status, 'Route:', route.name);
                                        
                                        switch(route.assignment_status) {
                                            case 'active':
                                                statusClass = 'success';
                                                statusIcon = 'fas fa-check-circle';
                                                statusBadge = 'Active';
                                                break;
                                            case 'expired':
                                                statusClass = 'danger';
                                                statusIcon = 'fas fa-times-circle';
                                                statusBadge = 'Expired';
                                                break;
                                            case 'upcoming':
                                                statusClass = 'info';
                                                statusIcon = 'fas fa-clock';
                                                statusBadge = 'Upcoming';
                                                break;
                                            case 'cancelled':
                                                statusClass = 'secondary';
                                                statusIcon = 'fas fa-ban';
                                                statusBadge = 'Cancelled';
                                                break;
                                            default:
                                                statusClass = 'light text-dark';
                                                statusIcon = 'fas fa-question';
                                                statusBadge = 'Unknown';
                                        }
                                        
                                        // Check if assignment is expiring soon (within 3 days)
                                        if (route.end_date && route.assignment_status === 'active') {
                                            const today = new Date();
                                            const endDate = new Date(route.end_date);
                                            const daysLeft = Math.ceil((endDate - today) / (1000 * 60 * 60 * 24));
                                            
                                            if (daysLeft <= 3 && daysLeft > 0) {
                                                warningBadge = `<span class="badge bg-warning text-dark ms-1" title="Expires in ${daysLeft} day(s)">
                                                    <i class="fas fa-exclamation-triangle"></i> ${daysLeft}d left
                                                </span>`;
                                            } else if (daysLeft <= 0) {
                                                warningBadge = `<span class="badge bg-danger ms-1" title="Assignment expired">
                                                    <i class="fas fa-exclamation-circle"></i> Expired
                                                </span>`;
                                            }
                                        }
                                        
                                        const sellBadge = route.can_sell ?
                                            '<span class="badge badge-sm bg-success ms-1"><i class="fas fa-shopping-cart"></i> Can Sell</span>' :
                                            '<span class="badge badge-sm bg-warning text-dark ms-1"><i class="fas fa-ban"></i> No Sell</span>';

                                        html += `
                                            <div class="route-item d-flex justify-content-between align-items-center mb-1 p-2 rounded ${route.assignment_status === 'expired' ? 'border-danger' : route.assignment_status === 'upcoming' ? 'border-info' : ''}">
                                                <div>
                                                    <span class="badge bg-primary me-1">
                                                        <i class="fas fa-route"></i> 
                                                        ${route.description ? `${route.description} (${route.name})` : route.name}
                                                    </span>
                                                    <span class="badge bg-${statusClass} me-1">
                                                        <i class="${statusIcon}"></i> ${statusBadge}
                                                    </span>
                                                    ${sellBadge}
                                                    ${warningBadge}
                                                </div>
                                                <div class="text-end">
                                                    <small class="text-muted d-block">
                                                        <i class="fas fa-calendar-plus"></i> From: ${new Date(route.assigned_date).toLocaleDateString()}
                                                    </small>
                                                    ${route.end_date ?
                                                        `<small class="${route.assignment_status === 'expired' ? 'text-danger' : 'text-muted'} d-block">
                                                            <i class="fas fa-calendar-minus"></i> To: ${new Date(route.end_date).toLocaleDateString()}
                                                        </small>` :
                                                        '<small class="text-success d-block"><i class="fas fa-infinity"></i> Ongoing</small>'
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
                            // Count assignments by status
                            const activeCount = row.assignments.filter(a => a.status === 'active').length;
                            const expiredCount = row.assignments.filter(a => a.status === 'expired').length;
                            const upcomingCount = row.assignments.filter(a => a.status === 'upcoming').length;
                            const cancelledCount = row.assignments.filter(a => a.status === 'cancelled').length;
                            
                            // Count expiring soon assignments
                            const today = new Date();
                            const expiringSoonCount = row.assignments.filter(a => {
                                if (a.status === 'active' && a.end_date) {
                                    const endDate = new Date(a.end_date);
                                    const daysLeft = Math.ceil((endDate - today) / (1000 * 60 * 60 * 24));
                                    return daysLeft <= 3 && daysLeft > 0;
                                }
                                return false;
                            }).length;
                            
                            let html = `
                                <div class="text-center">
                                    <div class="badge bg-info fs-6 mb-1">
                                        <i class="fas fa-list"></i> ${data} Total
                                    </div>
                                    <div class="d-flex flex-wrap justify-content-center gap-1">
                            `;
                            
                            if (activeCount > 0) {
                                html += `<small class="badge bg-success"><i class="fas fa-check-circle"></i> ${activeCount} Active</small>`;
                            }
                            if (expiredCount > 0) {
                                html += `<small class="badge bg-danger"><i class="fas fa-times-circle"></i> ${expiredCount} Expired</small>`;
                            }
                            if (upcomingCount > 0) {
                                html += `<small class="badge bg-info"><i class="fas fa-clock"></i> ${upcomingCount} Upcoming</small>`;
                            }
                            if (cancelledCount > 0) {
                                html += `<small class="badge bg-secondary"><i class="fas fa-ban"></i> ${cancelledCount} Cancelled</small>`;
                            }
                            if (expiringSoonCount > 0) {
                                html += `<small class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle"></i> ${expiringSoonCount} Expiring</small>`;
                            }
                            
                            html += `
                                    </div>
                                </div>
                            `;
                            
                            return html;
                        }
                    },
                    {
                        data: 'status',
                        render: function(data, type, row) {
                            // Determine overall user status based on assignments
                            const hasActive = row.assignments.some(a => a.status === 'active');
                            const hasExpiringSoon = row.assignments.some(a => {
                                if (a.status === 'active' && a.end_date) {
                                    const today = new Date();
                                    const endDate = new Date(a.end_date);
                                    const daysLeft = Math.ceil((endDate - today) / (1000 * 60 * 60 * 24));
                                    return daysLeft <= 3 && daysLeft > 0;
                                }
                                return false;
                            });
                            const hasExpired = row.assignments.some(a => a.status === 'expired');
                            const hasUpcoming = row.assignments.some(a => a.status === 'upcoming');
                            
                            let statusBadge = '';
                            let icon = '';
                            
                            if (hasActive) {
                                if (hasExpiringSoon) {
                                    statusBadge = `<span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle"></i> Active (Expiring Soon)</span>`;
                                } else {
                                    statusBadge = `<span class="badge bg-success"><i class="fas fa-check-circle"></i> Active</span>`;
                                }
                            } else if (hasUpcoming) {
                                statusBadge = `<span class="badge bg-info"><i class="fas fa-clock"></i> Upcoming Only</span>`;
                            } else if (hasExpired) {
                                statusBadge = `<span class="badge bg-danger"><i class="fas fa-times-circle"></i> Expired</span>`;
                            } else {
                                statusBadge = `<span class="badge bg-secondary"><i class="fas fa-ban"></i> Inactive</span>`;
                            }
                            
                            return `<div class="text-center">${statusBadge}</div>`;
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
                responsive: true,
                drawCallback: function(settings) {
                    // Check for expiring assignments and show notifications after table loads
                    checkExpiringAssignments();
                }
            });
        }

        // Check for expiring assignments and show notifications
        function checkExpiringAssignments() {
            const tableData = $('#salesRepsTable').DataTable().data().toArray();
            const today = new Date();
            let totalExpiringSoon = 0;
            let totalExpired = 0;
            let expiringAssignments = [];
            let expiredAssignments = [];

            tableData.forEach(userData => {
                userData.assignments.forEach(assignment => {
                    if (assignment.end_date) {
                        const endDate = new Date(assignment.end_date);
                        const daysLeft = Math.ceil((endDate - today) / (1000 * 60 * 60 * 24));
                        
                        if (assignment.status === 'active' && daysLeft <= 3 && daysLeft > 0) {
                            totalExpiringSoon++;
                            expiringAssignments.push({
                                user: userData.user?.user_name || 'Unknown',
                                location: assignment.sub_location?.name || 'Unknown',
                                route: assignment.route?.name || 'Unknown',
                                daysLeft: daysLeft,
                                endDate: endDate.toLocaleDateString()
                            });
                        } else if (assignment.status === 'expired' || (assignment.status === 'active' && daysLeft <= 0)) {
                            totalExpired++;
                            expiredAssignments.push({
                                user: userData.user?.user_name || 'Unknown',
                                location: assignment.sub_location?.name || 'Unknown',
                                route: assignment.route?.name || 'Unknown',
                                endDate: endDate.toLocaleDateString()
                            });
                        }
                    }
                });
            });

            // Show concise notifications
            if (totalExpiringSoon > 0) {
                toastr.warning(`⚠️ ${totalExpiringSoon} assignment(s) expiring soon!`, '', {
                    timeOut: 4000,
                    closeButton: true,
                    progressBar: true
                });
            }

            if (totalExpired > 0) {
                toastr.error(`🔴 ${totalExpired} assignment(s) expired!`, '', {
                    timeOut: 5000,
                    closeButton: true,
                    progressBar: true
                });
            }

            // Update status summary in header (if element exists)
            updateStatusSummary(totalExpiringSoon, totalExpired, tableData.length);
        }

        // Update status summary display
        function updateStatusSummary(expiringSoon, expired, totalUsers) {
            // Add or update status summary badge in the header
            let summaryBadge = $('#statusSummary');
            if (summaryBadge.length === 0) {
                // Create status summary element if it doesn't exist
                const headerTitle = $('.main-header h1, .content-header h1').first();
                if (headerTitle.length > 0) {
                    headerTitle.append(`
                        <div id="statusSummary" class="ms-3 d-inline-block">
                            <span class="badge bg-info"><i class="fas fa-users"></i> ${totalUsers} Users</span>
                        </div>
                    `);
                    summaryBadge = $('#statusSummary');
                }
            }

            if (summaryBadge.length > 0) {
                let badgesHtml = `<span class="badge bg-info"><i class="fas fa-users"></i> ${totalUsers} Users</span>`;
                
                if (expiringSoon > 0) {
                    badgesHtml += ` <span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle"></i> ${expiringSoon} Expiring</span>`;
                }
                
                if (expired > 0) {
                    badgesHtml += ` <span class="badge bg-danger"><i class="fas fa-times-circle"></i> ${expired} Expired</span>`;
                }
                
                summaryBadge.html(badgesHtml);
            }
        }

        // Auto-refresh status every 5 minutes
        let autoRefreshInterval;
        function startAutoRefresh() {
            // Clear existing interval if any
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
            }

            // Set up auto-refresh every 5 minutes (300000 ms)
            autoRefreshInterval = setInterval(function() {
                console.log('🔄 Auto-refreshing sales rep statuses...');
                
                // Call the status update API endpoint
                $.ajax({
                    url: '/api/sales-reps/update-statuses',
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.status && response.data.updated_count > 0) {
                            toastr.info(`🔄 ${response.data.updated_count} status updated.`);
                            // Refresh the table to show updated data
                            $('#salesRepsTable').DataTable().ajax.reload(null, false);
                        }
                    },
                    error: function(xhr) {
                        console.error('Auto-refresh failed:', xhr);
                    }
                });
            }, 300000); // 5 minutes

            console.log('✅ Auto-refresh enabled: Status updates every 5 minutes');
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
                rowCounter = 0; // Reset row counter
                addAssignmentRow();
            } else {
                $('#userInfoContainer').hide();
                $('#assignmentsContainer').hide();
                $('#assignmentRows').empty();
                rowCounter = 0; // Reset row counter
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
            
            // Populate locations dropdown (both available and already assigned locations)
            const locationSelect = $(`[data-row-id="${rowCounter}"] .sub-location-select`);
            if (locationsData && typeof locationsData === 'object') {
                // Get current user's existing assignments
                const userId = $('#user_id').val();
                const existingAssignments = [];
                
                if (userId) {
                    const tableData = $('#salesRepsTable').DataTable().data().toArray();
                    const userData = tableData.find(row => row.user_id == userId);
                    if (userData && userData.assignments) {
                        userData.assignments.forEach(assignment => {
                            if (assignment.status === 'active' && assignment.sub_location_id) {
                                existingAssignments.push({
                                    locationId: assignment.sub_location_id.toString(),
                                    routeId: assignment.route_id.toString(),
                                    routeName: assignment.route?.name || 'Unknown Route'
                                });
                            }
                        });
                    }
                }
                
                // Store existing assignments for later use
                $(`[data-row-id="${rowCounter}"]`).data('existing-assignments', existingAssignments);
                
                const assignedLocationIds = [...new Set(existingAssignments.map(a => a.locationId))];
                
                // Count available and assigned locations FIRST
                let availableCount = 0;
                let assignedCount = 0;
                
                Object.values(locationsData).forEach(loc => {
                    if (loc.parent_id) {
                        if (!assignedLocationIds.includes(loc.id.toString())) {
                            availableCount++;
                        } else {
                            assignedCount++;
                        }
                    }
                });
                
                // Clear the select and build clean structure
                locationSelect.empty().append('<option value="">Choose location...</option>');
                
                // Add new locations first (if any)
                if (availableCount > 0) {
                    locationSelect.append(`<optgroup label="🆕 New Locations (${availableCount})">`);
                    
                    Object.values(locationsData).forEach(loc => {
                        if (loc.parent_id && !assignedLocationIds.includes(loc.id.toString())) {
                            locationSelect.append(`<option value="${loc.id}">${loc.name}</option>`);
                        }
                    });
                    
                    locationSelect.append('</optgroup>');
                }
                
                // Add assigned locations (if any)
                if (assignedLocationIds.length > 0) {
                    locationSelect.append(`<optgroup label="➕ Add More Routes (${assignedLocationIds.length})">`);
                    
                    Object.values(locationsData).forEach(loc => {
                        if (loc.parent_id && assignedLocationIds.includes(loc.id.toString())) {
                            const locationAssignments = existingAssignments.filter(a => a.locationId === loc.id.toString());
                            const routeNames = locationAssignments.map(a => a.routeName).slice(0, 2);
                            const hasMore = locationAssignments.length > 2;
                            
                            let displayText = `${loc.name}`;
                            if (routeNames.length > 0) {
                                displayText += ` (${routeNames.join(', ')}${hasMore ? '+' : ''})`;
                            }
                            
                            locationSelect.append(`<option value="${loc.id}" class="assigned-location">${displayText}</option>`);
                            assignedCount++;
                        }
                    });
                    
                    locationSelect.append('</optgroup>');
                }
                
                console.log(`✅ Populated location dropdown: ${availableCount} new + ${assignedCount} assigned = ${availableCount + assignedCount} total options`);
                console.log(`Existing assignments for filtering:`, existingAssignments);
                
                if (availableCount === 0 && assignedCount === 0) {
                    locationSelect.append(`<option value="" disabled style="color: #dc3545; font-style: italic;">⚠️ No locations available</option>`);
                    console.warn('❌ No locations available for this user');
                } else {
                    // Add summary info
                    console.log(`📊 Location Summary:`, {
                        'New Locations': availableCount,
                        'Can Add Routes To': assignedCount,
                        'Total Options': availableCount + assignedCount
                    });
                }
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
            
            function populateRoutes(routes) {
                routesSelect.empty(); // Clear existing options
                if (routes && routes.length > 0) {
                    routes.forEach(route => {
                        const option = new Option(route.name, route.id, false, false);
                        routesSelect.append(option);
                    });
                    console.log(`Added ${routes.length} routes to row ${rowCounter}:`, routes.map(r => r.name));
                } else {
                    routesSelect.append(new Option('No routes available', '', true, true));
                    console.warn('No routes available to populate');
                }
                routesSelect.trigger('change');
            }
            
            if (Array.isArray(routesData) && routesData.length > 0) {
                console.log('Using cached routes data:', routesData.length, 'routes');
                populateRoutes(routesData);
            } else {
                console.log('Routes data not available, loading from API for row:', rowCounter);
                // Show loading indicator
                routesSelect.append(new Option('Loading routes...', '', true, true));
                
                // Load routes data if not available
                $.ajax({
                    url: '/api/routes',
                    method: 'GET',
                    beforeSend: function() {
                        console.log('Sending routes API request...');
                    },
                    success: function(res) {
                        console.log('Routes API response in addAssignmentRow:', res);
                        if (res.status && Array.isArray(res.data)) {
                            routesData = res.data.filter(r => r.status === 'active');
                            console.log('Filtered active routes:', routesData.length);
                            populateRoutes(routesData);
                        } else {
                            console.error('Invalid routes response structure:', res);
                            routesSelect.empty().append(new Option('Failed to load routes', '', true, true));
                            toastr.error('Invalid data format.');
                        }
                    },
                    error: (xhr) => {
                        console.error('Routes API request failed:', xhr.status, xhr.statusText);
                        console.error('Response:', xhr.responseText);
                        routesSelect.empty().append(new Option('Error loading routes', '', true, true));
                        toastr.error('Failed to load routes.');
                    }
                });
            }
            routesSelect.trigger('change');
            
            // Update remove button visibility
            updateRemoveButtons();
            
            // Update add button visibility
            updateAddButtons();
            
            // Update location availability
            updateLocationAvailability();
            
            // Add event listeners for this row
            bindRowEvents(rowCounter);
        }

        // Bind events for a specific row
        function bindRowEvents(rowId) {
            const rowSelector = `[data-row-id="${rowId}"]`;
            
            // Add row button
            $(rowSelector + ' .add-row-btn').on('click', function() {
                // Check if there are available locations before adding a new row
                const availableLocations = getAvailableLocations();
                if (availableLocations.length === 0) {
                    toastr.warning('No locations available.');
                    return;
                }
                addAssignmentRow();
                updateAddButtons(); // Update add button visibility after adding row
            });
            
            // Remove row button
            $(rowSelector + ' .remove-row-btn').on('click', function() {
                $(rowSelector).remove();
                updateRemoveButtons();
                updateAddButtons(); // Update add button visibility after removing row
                updateLocationAvailability(); // Update location availability after removing row
            });
            
            // Sub location change - Handle both new and existing location assignments
            $(rowSelector + ' .sub-location-select').on('change', function() {
                const locationId = $(this).val();
                const currentRowId = $(this).closest('.assignment-row').data('row-id');
                const currentRow = $(this).closest('.assignment-row');
                const routesSelect = currentRow.find('.routes-select');
                
                if (locationId) {
                    // Get existing assignments for this row
                    const existingAssignments = currentRow.data('existing-assignments') || [];
                    const locationAssignments = existingAssignments.filter(a => a.locationId === locationId);
                    
                    if (locationAssignments.length > 0) {
                        // This is an already assigned location - filter routes to show only unassigned ones
                        console.log(`Location ${locationId} is already assigned with routes:`, locationAssignments.map(a => a.routeName));
                        
                        // Show info message
                        toastr.info('Location already assigned - adding route.');
                        
                        // Filter routes to exclude already assigned ones
                        filterRoutesForLocation(locationId, routesSelect, locationAssignments);
                    } else {
                        // This is a new location - show all routes
                        console.log(`New location ${locationId} selected`);
                        
                        // Check if this location is already selected in another row (form validation)
                        if (isLocationAlreadySelectedInForm(locationId, currentRowId)) {
                            toastr.error('Location already selected.');
                            $(this).val('').trigger('change');
                            return;
                        }
                        
                        // Show all routes for new location
                        resetRoutesDropdown(routesSelect);
                        
                        // Check for date conflicts with other sales reps
                        const assignedDate = currentRow.find('input[name*="assigned_date"]').val();
                        const endDate = currentRow.find('input[name*="end_date"]').val();
                        
                        if (assignedDate) {
                            checkLocationDateConflict(locationId, assignedDate, endDate, currentRowId);
                        }
                    }
                } else {
                    // No location selected - reset routes
                    resetRoutesDropdown(routesSelect);
                }
                
                console.log('Selected location:', locationId, locationsData[locationId]);
                updateLocationAvailability(); // Update availability for all rows
            });
            
            // Date change validation
            $(rowSelector + ' input[name*="assigned_date"], ' + rowSelector + ' input[name*="end_date"]').on('change', function() {
                const row = $(this).closest('.assignment-row');
                const locationId = row.find('.sub-location-select').val();
                const assignedDate = row.find('input[name*="assigned_date"]').val();
                const endDate = row.find('input[name*="end_date"]').val();
                const currentRowId = row.data('row-id');
                
                if (locationId && assignedDate) {
                    checkLocationDateConflict(locationId, assignedDate, endDate, currentRowId);
                }
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

        // Update add button visibility - only show on the last row
        function updateAddButtons() {
            const rows = $('.assignment-row');
            rows.each(function(index) {
                const addBtn = $(this).find('.add-row-btn');
                if (index === rows.length - 1) {
                    // Check if there are available locations
                    const availableLocations = getAvailableLocations();
                    if (availableLocations.length > 0) {
                        addBtn.show();
                    } else {
                        addBtn.hide();
                    }
                } else {
                    // Not the last row, hide the add button
                    addBtn.hide();
                }
            });
        }

        // Get list of available locations for assignment
        function getAvailableLocations() {
            const userId = $('#user_id').val();
            const selectedLocationsInForm = [];
            const existingAssignedLocations = [];
            
            // Get selected locations in current form
            $('.assignment-row').each(function() {
                const selectedLocation = $(this).find('.sub-location-select').val();
                if (selectedLocation) {
                    selectedLocationsInForm.push(selectedLocation);
                }
            });
            
            // Get existing assignments for this user
            if (userId) {
                const tableData = $('#salesRepsTable').DataTable().data().toArray();
                const userData = tableData.find(row => row.user_id == userId);
                if (userData && userData.assignments) {
                    userData.assignments.forEach(assignment => {
                        if (assignment.status === 'active' && assignment.sub_location_id) {
                            existingAssignedLocations.push(assignment.sub_location_id.toString());
                        }
                    });
                }
            }
            
            // Get all unavailable locations
            const unavailableLocations = [...selectedLocationsInForm, ...existingAssignedLocations];
            
            // Filter available locations
            const availableLocations = [];
            if (locationsData && typeof locationsData === 'object') {
                Object.values(locationsData).forEach(loc => {
                    if (loc.parent_id && !unavailableLocations.includes(loc.id.toString())) {
                        availableLocations.push(loc);
                    }
                });
            }
            
            // Debug logging
            if (userId) {
                console.log('Assignment Analysis for User ID:', userId);
                console.log('Selected in form:', selectedLocationsInForm);
                console.log('Existing assigned:', existingAssignedLocations);
                console.log('Available locations:', availableLocations.map(loc => `${loc.id}: ${loc.name}`));
            }
            
            return availableLocations;
        }

        // Check if location is already selected in current form (renamed for clarity)
        function isLocationAlreadySelectedInForm(locationId, currentRowId) {
            let isSelected = false;
            $('.assignment-row').each(function() {
                const rowId = $(this).data('row-id');
                if (rowId != currentRowId) {
                    const selectedLocation = $(this).find('.sub-location-select').val();
                    if (selectedLocation == locationId) {
                        isSelected = true;
                        return false; // break the loop
                    }
                }
            });
            return isSelected;
        }

        // Filter routes dropdown to exclude already assigned routes for a location
        function filterRoutesForLocation(locationId, routesSelect, locationAssignments) {
            const assignedRouteIds = locationAssignments.map(a => a.routeId);
            
            console.log(`Filtering routes for location ${locationId}. Assigned route IDs:`, assignedRouteIds);
            
            // Clear and repopulate routes dropdown
            routesSelect.empty();
            
            let availableRoutesCount = 0;
            if (Array.isArray(routesData) && routesData.length > 0) {
                routesData.forEach(route => {
                    if (!assignedRouteIds.includes(route.id.toString())) {
                        const option = new Option(route.name, route.id, false, false);
                        routesSelect.append(option);
                        availableRoutesCount++;
                    }
                });
            }
            
            if (availableRoutesCount === 0) {
                routesSelect.append(new Option('All routes already assigned to this location', '', true, true));
                toastr.warning('No routes available.');
            } else {
                console.log(`${availableRoutesCount} routes available for location ${locationId}`);
            }
            
            routesSelect.trigger('change');
        }

        // Reset routes dropdown to show all routes
        function resetRoutesDropdown(routesSelect) {
            routesSelect.empty();
            
            if (Array.isArray(routesData) && routesData.length > 0) {
                routesData.forEach(route => {
                    const option = new Option(route.name, route.id, false, false);
                    routesSelect.append(option);
                });
                console.log(`Reset routes dropdown with ${routesData.length} routes`);
            } else {
                routesSelect.append(new Option('No routes available', '', true, true));
            }
            
            routesSelect.trigger('change');
        }

        // Update location availability in all rows (now mainly for form-level duplicates)
        function updateLocationAvailability() {
            const userId = $('#user_id').val();
            
            $('.assignment-row').each(function() {
                const currentRow = $(this);
                const currentRowId = currentRow.data('row-id');
                const locationSelect = currentRow.find('.sub-location-select');
                const currentValue = locationSelect.val();
                
                // Get all selected locations from other rows in the form (for form-level duplicates)
                const selectedLocationsInForm = [];
                $('.assignment-row').each(function() {
                    const rowId = $(this).data('row-id');
                    if (rowId != currentRowId) {
                        const selectedLocation = $(this).find('.sub-location-select').val();
                        if (selectedLocation) {
                            selectedLocationsInForm.push(selectedLocation);
                        }
                    }
                });
                
                // Update options availability (only disable form-level duplicates since DB duplicates are already filtered out)
                locationSelect.find('option').each(function() {
                    const optionValue = $(this).val();
                    const originalText = $(this).data('original-text') || $(this).text().replace(/ \(.*\)$/, '');
                    
                    // Store original text if not already stored
                    if (!$(this).data('original-text')) {
                        $(this).data('original-text', originalText);
                    }
                    
                    if (optionValue && selectedLocationsInForm.includes(optionValue)) {
                        $(this).prop('disabled', true);
                        $(this).text(originalText + ' (Selected in Form)');
                    } else {
                        $(this).prop('disabled', false);
                        $(this).text(originalText);
                    }
                });
                
                // Add helpful message if current value becomes invalid
                if (currentValue && selectedLocationsInForm.includes(currentValue)) {
                    console.warn(`Location ${currentValue} is already selected in another row ${currentRowId}`);
                }
            });
            
            // Update summary of available locations
            const availableCount = getAvailableLocations().length;
            console.log(`Location availability updated. ${availableCount} locations available for assignment.`);
        }

        // Check for date conflicts with existing assignments
        function checkLocationDateConflict(locationId, assignedDate, endDate, currentRowId) {
            const userId = $('#user_id').val();
            
            // Check against existing data in the table
            const tableData = $('#salesRepsTable').DataTable().data().toArray();
            
            for (let userData of tableData) {
                // Skip if checking for the same user (when editing)
                if (userData.user_id == userId) continue;
                
                for (let assignment of userData.assignments) {
                    // Only check conflicts with ACTIVE assignments (ignore expired, cancelled, etc.)
                    if (assignment.sub_location_id == locationId && assignment.status === 'active') {
                        const existingStart = new Date(assignment.assigned_date);
                        const existingEnd = assignment.end_date ? new Date(assignment.end_date) : null;
                        const newStart = new Date(assignedDate);
                        const newEnd = endDate ? new Date(endDate) : null;
                        
                        // Check for date overlap
                        let hasConflict = false;
                        
                        if (!existingEnd && !newEnd) {
                            // Both are ongoing
                            hasConflict = true;
                        } else if (!existingEnd && newEnd) {
                            // Existing is ongoing, new has end date
                            hasConflict = newStart <= new Date() && newEnd >= existingStart;
                        } else if (existingEnd && !newEnd) {
                            // Existing has end date, new is ongoing
                            hasConflict = newStart <= existingEnd;
                        } else if (existingEnd && newEnd) {
                            // Both have end dates
                            hasConflict = !(newEnd < existingStart || newStart > existingEnd);
                        }
                        
                        if (hasConflict) {
                            const locationName = locationsData[locationId]?.name || 'Unknown Location';
                            const conflictUser = userData.user?.user_name || 'Unknown User';
                            const existingEndText = existingEnd ? existingEnd.toLocaleDateString() : 'Ongoing';
                            
                            toastr.error(
                                `Location "${locationName}" is already assigned to "${conflictUser}" ` +
                                `from ${existingStart.toLocaleDateString()} to ${existingEndText}. ` +
                                `Please choose different dates or location.`
                            );
                            
                            // Clear the conflicting row
                            const conflictRow = $(`[data-row-id="${currentRowId}"]`);
                            conflictRow.find('.sub-location-select').val('').trigger('change');
                            conflictRow.find('input[name*="assigned_date"]').val('');
                            conflictRow.find('input[name*="end_date"]').val('');
                            
                            return false;
                        }
                    }
                }
            }
            return true;
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
            validationErrors = []; // Reset validation errors
            
            loadDropdownData();
            $('#addAndEditSalesRepModal').modal('show');
            
            // Log validation features
            console.log('Sales Rep Assignment Validations Active:');
            console.log('1. Duplicate location prevention for same sales rep');
            console.log('2. Date conflict detection between different sales reps');
            console.log('3. Real-time location availability updates');
            console.log('4. Add Row button only shows on last row');
            console.log('5. Add Row button hides when no locations available');
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
            const usedLocations = new Set();
            let hasValidationError = false;
            
            $('.assignment-row').each(function() {
                const row = $(this);
                const subLocationId = row.find('.sub-location-select').val();
                const routeIds = row.find('.routes-select').val();
                const canSell = row.find('input[name*="can_sell"]').is(':checked');
                const assignedDate = row.find('input[name*="assigned_date"]').val();
                const endDate = row.find('input[name*="end_date"]').val();
                const status = row.find('select[name*="status"]').val();

                // Handle both single route (edit mode) and multiple routes (create mode)
                let routesForValidation = routeIds;
                if (routeIds && !Array.isArray(routeIds)) {
                    // Single route selected (edit mode) - convert to array for consistent processing
                    routesForValidation = [routeIds];
                } else if (!routeIds || (Array.isArray(routeIds) && routeIds.length === 0)) {
                    // No routes selected
                    routesForValidation = [];
                }
                
                if (subLocationId && routesForValidation && routesForValidation.length > 0) {
                    // Check for duplicate locations within the current form (not DB duplicates)
                    if (usedLocations.has(subLocationId)) {
                        toastr.error('Duplicate location not allowed.');
                        hasValidationError = true;
                        return false;
                    }
                    usedLocations.add(subLocationId);
                    
                    // Check if routes are actually available (not already assigned to this location)
                    const existingAssignments = row.data('existing-assignments') || [];
                    const locationAssignments = existingAssignments.filter(a => a.locationId === subLocationId);
                    
                    if (locationAssignments.length > 0) {
                        const assignedRouteIds = locationAssignments.map(a => a.routeId);
                        const duplicateRoutes = routesForValidation.filter(routeId => assignedRouteIds.includes(routeId.toString()));
                        
                        if (duplicateRoutes.length > 0) {
                            const duplicateRouteNames = duplicateRoutes.map(routeId => {
                                const route = routesData.find(r => r.id.toString() === routeId.toString());
                                return route ? route.name : routeId;
                            });
                            toastr.error('Route already assigned to location.');
                            hasValidationError = true;
                            return false;
                        }
                    }
                    
                    assignments.push({
                        sub_location_id: subLocationId,
                        route_ids: routesForValidation, // This will be array in both cases now
                        can_sell: canSell,
                        assigned_date: assignedDate,
                        end_date: endDate || null,
                        status: status
                    });
                }
            });

            if (hasValidationError) {
                return;
            }

            if (assignments.length === 0) {
                toastr.error('Complete at least one assignment.');
                return;
            }

            // Check if this is an edit operation
            const assignmentId = $('#sales_rep_id').val();
            const isEditMode = assignmentId && assignmentId !== '';
            
            let formData, ajaxUrl, ajaxMethod;
            
            if (isEditMode) {
                // Edit mode - update existing assignment (single route only)
                if (assignments.length > 1) {
                    toastr.error('Edit one assignment at a time.');
                    return;
                }
                
                // Get route data properly - in edit mode, route_ids might be a single value or array
                let routeId;
                const routeData = assignments[0].route_ids;
                
                if (Array.isArray(routeData)) {
                    if (routeData.length > 1) {
                        toastr.error('One route per assignment in edit mode.');
                        return;
                    }
                    routeId = routeData[0];
                } else {
                    // Single route value
                    routeId = routeData;
                }
                
                if (!routeId) {
                    toastr.error('Select a route.');
                    return;
                }
                
                console.log('Edit mode - sending single route_id:', routeId);
                
                formData = {
                    user_id: userId,
                    sub_location_id: assignments[0].sub_location_id,
                    route_id: routeId, // Single route ID, not array
                    can_sell: assignments[0].can_sell,
                    assigned_date: assignments[0].assigned_date,
                    end_date: assignments[0].end_date || null,
                    status: assignments[0].status,
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    _method: 'PUT'
                };
                ajaxUrl = `/api/sales-reps/${assignmentId}`;
                ajaxMethod = 'POST'; // Laravel uses POST with _method for PUT
            } else {
                // Create mode - new assignments
                formData = {
                    user_id: userId,
                    assignments: assignments,
                    _token: $('meta[name="csrf-token"]').attr('content')
                };
                ajaxUrl = '/api/sales-reps';
                ajaxMethod = 'POST';
            }

            console.log(`Submitting ${isEditMode ? 'UPDATE' : 'CREATE'} request:`, formData);
            console.log(`AJAX URL: ${ajaxUrl}, Method: ${ajaxMethod}`);

            $.ajax({
                url: ajaxUrl,
                method: ajaxMethod,
                data: formData,
                success: function(res) {
                    console.log('Success response:', res);
                    $('#addAndEditSalesRepModal').modal('hide');
                    loadDataTable();
                    
                    if (isEditMode) {
                        toastr.success(res.message || 'Assignment updated!');
                    } else {
                        toastr.success(res.message || `${assignments.length} assignment(s) created!`);
                    }
                },
                error: function(xhr) {
                    console.error('Error response:', xhr);
                    const errors = xhr.responseJSON?.errors || {};
                    $('.text-danger').text('');
                    Object.keys(errors).forEach(key => {
                        $(`#${key}_error`).text(Array.isArray(errors[key]) ? errors[key][0] : errors[key]);
                    });
                    toastr.error(xhr.responseJSON?.message || 'Operation failed.');
                }
            });
        });

        // Edit assignment
        $('#salesRepsTable').on('click', '.editBtn', function() {
            const assignmentId = $(this).data('id');
            editAssignment(assignmentId);
        });

        // Assign another route
        $('#salesRepsTable').on('click', '.addRouteBtn', function() {
            const userId = $(this).data('user');
            console.log('Adding route for user:', userId);
            
            // Get user data from the table
            const tableData = $('#salesRepsTable').DataTable().data().toArray();
            const userData = tableData.find(row => row.user_id == userId);
            
            if (!userData || !userData.user) {
                toastr.error('User data not found.');
                return;
            }
            
            // Reset form
            $('#salesRepAddUpdateForm')[0].reset();
            $('#sales_rep_id').val('');
            rowCounter = 0; // Reset row counter
            
            // Set modal title and button text
            $('#modalTitle').html('<i class="fas fa-route me-2"></i>Add Route to ' + userData.user.user_name);
            $('#saveBtn').html('<i class="fas fa-save me-2"></i>Save Assignment');
            $('.text-danger').text('');
            
            // Show user information
            const user = userData.user;
            $('#selectedUserName').text(user.user_name);
            $('#selectedUserEmail').text(user.email);
            $('#selectedUserRole').text(user.role_key || 'Sales Rep');
            
            // Show current assignments info
            const currentAssignments = userData.assignments.filter(a => a.status === 'active');
            const assignmentInfo = currentAssignments.map(assignment => {
                const locationName = assignment.sub_location?.name || 'Unknown Location';
                const routeName = assignment.route?.name || 'Unknown Route';
                return `${locationName} → ${routeName}`;
            }).join(', ');
            
            // Add current assignments info to the user info
            $('#selectedUserRole').parent().append(`
                <div id="currentAssignmentsInfo" class="mt-2">
                    <small class="text-muted">
                        <strong>Current Active Assignments:</strong><br>
                        ${assignmentInfo || 'No active assignments'}
                    </small>
                </div>
            `);
            
            $('#userInfoContainer').show();
            $('#assignmentsContainer').show();
            
            // Load dropdown data first
            loadDropdownData().then(() => {
                // Set user after dropdown is loaded
                $('#user_id').val(userId).prop('disabled', true);
                console.log('User selected in dropdown for adding route:', userId);
                
                // Clear and add first row
                $('#assignmentRows').empty();
                addAssignmentRow();
                
                // Update location availability after a short delay to ensure DOM is ready
                setTimeout(() => {
                    updateLocationAvailability();
                    
                    // Check if there are any locations available (including assigned ones for route additions)
                    const locationOptions = $('.assignment-row .sub-location-select option:not([value=""])');
                    const availableLocationOptions = locationOptions.filter(':not(:disabled)');
                    
                    console.log(`Location dropdown check: ${locationOptions.length} total options, ${availableLocationOptions.length} selectable`);
                    
                    if (availableLocationOptions.length === 0) {
                        toastr.warning('No locations available.');
                        $('#addAndEditSalesRepModal').modal('hide');
                        return;
                    } else {
                        // Set default date to today
                        const today = new Date().toISOString().split('T')[0];
                        $('.assignment-row input[name*="assigned_date"]').val(today);
                        
                        // Count new vs assigned locations
                        const newLocations = availableLocationOptions.filter(':not(.assigned-location)').length;
                        const assignedLocations = availableLocationOptions.filter('.assigned-location').length;
                        
                        let message = 'Ready to add routes! ';
                        if (newLocations > 0 && assignedLocations > 0) {
                            message += `${newLocations} new location(s) and ${assignedLocations} assigned location(s) available.`;
                        } else if (newLocations > 0) {
                            message += `${newLocations} new location(s) available for assignment.`;
                        } else if (assignedLocations > 0) {
                            message += `${assignedLocations} assigned location(s) available for additional routes.`;
                        }
                        
                        toastr.success(message);
                        
                        // Log available locations for debugging
                        const availableLocationNames = [];
                        availableLocationOptions.each(function() {
                            availableLocationNames.push($(this).text());
                        });
                        console.log('Available locations:', availableLocationNames);
                        
                        // Focus on the location select for better UX
                        setTimeout(() => {
                            $('.assignment-row .sub-location-select').first().focus();
                        }, 100);
                    }
                }, 300); // Increased timeout to ensure DOM is fully ready
            }).catch(error => {
                console.error('Failed to load dropdown data:', error);
                toastr.error('Failed to load form data.');
            });
            
            // Show the modal
            $('#addAndEditSalesRepModal').modal('show');
            
            // Clean up current assignments info when modal closes
            $('#addAndEditSalesRepModal').on('hidden.bs.modal', function() {
                $('#currentAssignmentsInfo').remove();
            });
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
                // Call the manage assignments functionality
                showManageAssignmentsModal(userData, userId);
            }
        });

        // View Details Modal (Read-only)
        function showUserDetailsModal(userData, userId) {
            let detailsHtml = `
                <div class="modal fade" id="userDetailsModal" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header bg-info text-white">
                                <h5 class="modal-title">
                                    <i class="fas fa-eye me-2"></i>
                                    ${userData.user.user_name} - View Details
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
                            <span class="badge bg-secondary">
                                ${assignment.route?.description ? `${assignment.route.description} (${assignment.route.name})` : assignment.route?.name || 'N/A'}
                            </span>
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
            
            // Handle edit assignment within modal
            $('#userDetailsModal').on('click', '.editAssignmentBtn', function() {
                const assignmentId = $(this).data('id');
                $('#userDetailsModal').modal('hide');
                
                // Wait for modal to hide, then trigger the edit functionality
                setTimeout(function() {
                    // Trigger the edit button click with the assignment ID
                    const editBtn = $(`<button data-id="${assignmentId}"></button>`);
                    editBtn.trigger('click');
                    // Manually trigger the edit function
                    editAssignment(assignmentId);
                }, 300);
            });

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
                            toastr.success(res.message || 'Assignment deleted!');
                        },
                        error: function(xhr) {
                            console.error('Delete error:', xhr);
                            toastr.error('Delete failed.');
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

        // Manage Assignments Modal (With Edit/Delete Actions)
        function showManageAssignmentsModal(userData, userId) {
            let manageHtml = `
                <div class="modal fade" id="manageAssignmentsModal" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header bg-warning text-dark">
                                <h5 class="modal-title">
                                    <i class="fas fa-edit me-2"></i>
                                    ${userData.user.user_name} - Manage Assignments
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                                <h6><i class="fas fa-cogs me-2"></i>Manage All Assignments</h6>
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
                manageHtml += `
                    <tr>
                        <td>${assignment.id}</td>
                        <td>
                            <span class="badge bg-info">${assignment.sub_location?.name || 'N/A'}</span>
                            <small class="d-block text-muted">${assignment.sub_location?.full_name || ''}</small>
                        </td>
                        <td>
                            <span class="badge bg-secondary">
                                ${assignment.route?.description ? `${assignment.route.description} (${assignment.route.name})` : assignment.route?.name || 'N/A'}
                            </span>
                        </td>
                        <td>${new Date(assignment.assigned_date).toLocaleDateString()}</td>
                        <td>${assignment.end_date ? new Date(assignment.end_date).toLocaleDateString() : '<span class="text-success">Ongoing</span>'}</td>
                        <td>
                            <span class="badge bg-${assignment.can_sell ? 'success' : 'warning'}">
                                ${assignment.can_sell ? 'Yes' : 'No'}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-${assignment.status === 'active' ? 'success' : assignment.status === 'expired' ? 'danger' : 'secondary'}">
                                ${assignment.status.charAt(0).toUpperCase() + assignment.status.slice(1)}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-outline-primary editAssignmentBtn" 
                                    data-id="${assignment.id}" title="Edit Assignment">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger deleteAssignmentBtn" 
                                    data-id="${assignment.id}" title="Delete Assignment">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            manageHtml += `
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
            $('#manageAssignmentsModal').remove();
            
            // Add modal to body and show
            $('body').append(manageHtml);
            $('#manageAssignmentsModal').modal('show');

            // Handle edit assignment within modal
            $('#manageAssignmentsModal').on('click', '.editAssignmentBtn', function() {
                const assignmentId = $(this).data('id');
                $('#manageAssignmentsModal').modal('hide');
                
                setTimeout(function() {
                    editAssignment(assignmentId);
                }, 300);
            });

            // Handle delete assignment within modal
            $('#manageAssignmentsModal').on('click', '.deleteAssignmentBtn', function() {
                const assignmentId = $(this).data('id');
                if (confirm('Are you sure you want to delete this assignment?')) {
                    $.ajax({
                        url: `/api/sales-reps/${assignmentId}`,
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                        success: function(res) {
                            $('#manageAssignmentsModal').modal('hide');
                            loadDataTable();
                            toastr.success(res.message || 'Assignment deleted!');
                        },
                        error: function(xhr) {
                            console.error('Delete error:', xhr);
                            toastr.error('Delete failed.');
                        }
                    });
                }
            });

            // Handle add route within modal
            $('#manageAssignmentsModal').on('click', '.addRouteBtn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const userId = $(this).data('user');
                $('#manageAssignmentsModal').modal('hide');
                
                setTimeout(function() {
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
                    
                    loadDropdownData().then(() => {
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
                    toastr.success(res.message || 'Sales Rep deleted!');
                },
                error: function(xhr) {
                    console.error('Delete error:', xhr);
                    toastr.error(xhr.responseJSON?.message || 'Delete failed.');
                }
            });
        });

    </script>

@endsection