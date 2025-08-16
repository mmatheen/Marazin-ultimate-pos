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
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Vehicle</th>
                                            <th>Location</th>
                                            <th>Routes</th>
                                            <th>Cities Covered</th>
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

        <!-- Add/Edit Modal -->
        <div class="modal fade" id="addAndEditSalesRepModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="text-center mt-2 mb-4">
                            <h5 id="modalTitle">Assign Route to Sales Rep</h5>
                        </div>
                        <form id="salesRepAddUpdateForm">
                            @csrf
                            <input type="hidden" name="id" id="sales_rep_id">

                            <!-- User (Readonly) -->
                            <div class="mb-3">
                                <label>User <span class="text-danger">*</span></label>
                                <select name="user_id" id="user_id" class="form-control" style="display:none;"></select>
                                <input type="hidden" name="user_id" id="user_id_hidden" required>
                                <div id="user_display" class="form-control-plaintext"
                                    style="border:1px solid #ddd; border-radius:5px; padding:5px; background:#f9f9f9;">
                                    Select User
                                </div>
                                <span class="text-danger" id="user_id_error"></span>
                            </div>

                            <!-- Vehicle (Readonly) -->
                            <div class="mb-3">
                                <label>Vehicle <span class="text-danger">*</span></label>
                                <select name="vehicle_id" id="vehicle_id" class="form-control"
                                    style="display:none;"></select>
                                <input type="hidden" name="vehicle_id" id="vehicle_id_hidden" required>
                                <div id="vehicle_display" class="form-control-plaintext"
                                    style="border:1px solid #ddd; border-radius:5px; padding:5px; background:#f9f9f9;">
                                    Select Vehicle
                                </div>
                                <span class="text-danger" id="vehicle_id_error"></span>
                                <small class="form-text text-muted">Location will be auto-filled based on vehicle.</small>
                            </div>



                            <div class="mb-3">
                                <label>Route <span class="text-danger">*</span></label>
                                <select name="route_id" id="route_id" class="form-control" required>
                                    <option value="">Select Route</option>
                                </select>
                                <span class="text-danger" id="route_id_error"></span>
                            </div>

                            <div class="mb-3">
                                <label>Assigned Date</label>
                                <input type="date" name="assigned_date" id="assigned_date" class="form-control">
                            </div>

                            {{-- end date --}}
                            <div class="mb-3">
                                <label>End Date (Optional)</label>
                                <input type="date" name="end_date" id="end_date" class="form-control">
                            </div>


                            <div class="mb-3">
                                <label>Status</label>
                                <select name="status" id="status" class="form-control">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>

                            <div class="modal-footer">
                                <button type="submit" id="saveBtn" class="btn btn-outline-primary">Assign</button>
                                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

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

    <script>
        $(document).ready(function() {
            if ($.fn.DataTable.isDataTable('#salesRepsTable')) {
                $('#salesRepsTable').DataTable().destroy();
            }

            const table = $('#salesRepsTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: "{{ url('/api/sales-reps') }}",
                    type: "GET",
                    dataSrc: function(res) {
                        if (!res.status) {
                            toastr.error(res.message || 'Failed to load data.');
                            return [];
                        }
                        return res.data;
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON?.message || 'Failed to load data.');
                    }
                },
                columns: [{
                        data: 'user_id'
                    },
                    {
                        data: 'user.user_name',
                        render: (data) => data || '—'
                    },
                    {
                        data: 'user.email',
                        render: (data) => data || '—'
                    },
                    {
                        data: 'vehicle.vehicle_number',
                        render: (data) => data || '—'
                    },
                    {
                        data: 'location.full_name',
                        render: (data) => data ? data : '<span class="text-muted">No Location</span>'
                    },
                    {
                        data: 'routes',
                        render: function(routes) {
                            if (!routes || routes.length === 0) {
                                return '<span class="text-muted">No Routes</span>';
                            }
                            return routes.map(r => {
                                const statusColor = r.status === 'active' ? 'success' :
                                    'secondary';
                                return `
                                    <div class="mb-1">
                                        <span class="badge bg-info">${r.name}</span>
                                        <span class="badge bg-${statusColor}">${r.status.charAt(0).toUpperCase() + r.status.slice(1)}</span>
                                    </div>
                                `;
                            }).join('');
                        }
                    },
                    {
                        data: 'routes',
                        render: function(routes) {
                            if (!routes || routes.length === 0) {
                                return '<span class="text-muted">No Cities</span>';
                            }
                            return routes.map(r => {
                                const cities = r.cities?.map(c => c.name) || [];
                                const display = cities.length > 3 ?
                                    `${cities.slice(0, 3).join(', ')} <small>+${cities.length - 3} more</small>` :
                                    cities.join(', ');
                                return `<div><strong>${r.name}:</strong> ${display}</div>`;
                            }).join('');
                        }
                    },
                    {
                        data: 'status',
                        render: (data) => {
                            const color = data === 'active' ? 'success' : 'secondary';
                            return `<span class="badge bg-${color}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>`;
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: (data) => `
                            <button class='btn btn-sm btn-info editBtn me-1' data-id='${data.routes[0]?.id || data.id}' title='Edit Assignment'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class='btn btn-sm btn-danger deleteBtn' data-id='${data.routes[0]?.id || data.id}' title='Delete Assignment'>
                                <i class="fas fa-trash"></i>
                            </button>
                            <button class='btn btn-sm btn-success addRouteBtn mt-1' 
                                data-user='${data.user_id}' 
                                data-vehicle='${data.vehicle_id}' 
                                title='Assign Another Route'>
                                <i class="fas fa-plus"></i> Add Route
                            </button>
                        `
                    }
                ]
            });

            function loadDropdowns(userId = null, vehicleId = null) {
                // Show selects, hide display divs
                $('#user_id').show();
                $('#vehicle_id').show();
                $('#user_display').hide();
                $('#vehicle_display').hide();

                // Load Users
                $.ajax({
                    url: '/user-get-all',
                    method: 'GET',
                    success: function(res) {
                        $('#user_id').empty().append('<option value="">Select User</option>');
                        if (res.message && Array.isArray(res.message)) {
                            res.message
                                .filter(u => u.role_key === 'sales_rep') // ✅ Filter by canonical key
                                .forEach(u => {
                                    $('#user_id').append(
                                        `<option value="${u.id}">${u.user_name} (${u.email})</option>`
                                    );
                                });
                        }
                        if (userId) $('#user_id').val(userId);
                    },
                    error: () => toastr.error('Failed to load users.')
                });

                // Load Vehicles
                $.ajax({
                    url: '/api/vehicles',
                    method: 'GET',
                    success: function(res) {
                        $('#vehicle_id').empty().append('<option value="">Select Vehicle</option>');
                        if (res.status && Array.isArray(res.data)) {
                            res.data.forEach(v => {
                                $('#vehicle_id').append(
                                    `<option value="${v.id}">${v.vehicle_number || v.id}</option>`
                                );
                            });
                        }
                        if (vehicleId) $('#vehicle_id').val(vehicleId);
                    },
                    error: () => toastr.error('Failed to load vehicles.')
                });

                // Load Routes
                $.ajax({
                    url: '/api/routes',
                    method: 'GET',
                    success: function(res) {
                        $('#route_id').empty().append('<option value="">Select Route</option>');
                        if (res.status && Array.isArray(res.data)) {
                            res.data
                                .filter(r => r.status === 'active')
                                .forEach(r => {
                                    $('#route_id').append(
                                        `<option value="${r.id}">${r.name}</option>`
                                    );
                                });
                        }
                    },
                    error: () => toastr.error('Failed to load routes.')
                });
            }

            $('#addSalesRepButton').on('click', function() {
                $('#salesRepAddUpdateForm')[0].reset();
                $('#sales_rep_id').val('');
                $('#user_id_hidden').val('');
                $('#vehicle_id_hidden').val('');

                // Show selects
                $('#user_id').show();
                $('#vehicle_id').show();
                $('#user_display').hide();
                $('#vehicle_display').hide();

                $('#modalTitle').text('Assign Route to Sales Rep');
                $('#saveBtn').text('Assign');
                $('.text-danger').text('');
                loadDropdowns();
                $('#addAndEditSalesRepModal').modal('show');
            });

            $('#salesRepsTable').on('click', '.editBtn', function() {
                const id = $(this).data('id');
                $.get(`/api/sales-reps/${id}`, function(res) {
                    if (res.status && res.data) {
                        const d = res.data;

                        // Reset form fields
                        $('#sales_rep_id').val(d.id);
                        $('#user_id_hidden').val(d.user_id); // ✅ Populate hidden input
                        $('#vehicle_id_hidden').val(d.vehicle_id); // ✅ Populate hidden input

                        // Show selects
                        $('#user_id').show();
                        $('#vehicle_id').show();
                        $('#user_display').hide();
                        $('#vehicle_display').hide();

                        // Set values for selects
                        $('#user_id').val(d.user_id);
                        $('#vehicle_id').val(d.vehicle_id);
                        $('#route_id').val(d.route_id);
                        $('#assigned_date').val(d.assigned_date?.split('T')[0] || '');
                        $('#status').val(d.status);

                        // Set modal title
                        $('#modalTitle').text(`Edit Route Assignment`);
                        $('#saveBtn').text('Update');
                        $('.text-danger').text('');
                        loadDropdowns(d.user_id, d.vehicle_id);
                        $('#addAndEditSalesRepModal').modal('show');
                    } else {
                        toastr.error('Could not load assignment.');
                    }
                }).fail(() => toastr.error('Load failed.'));
            });

            $('#salesRepsTable').on('click', '.addRouteBtn', function() {
                const userId = $(this).data('user');
                const vehicleId = $(this).data('vehicle');

                // Reset form
                $('#salesRepAddUpdateForm')[0].reset();
                $('#sales_rep_id').val('');
                $('#user_id_hidden').val('');
                $('#vehicle_id_hidden').val('');
                $('#user_display').text('Select User');
                $('#vehicle_display').text('Select Vehicle');

                // Populate hidden fields
                $('#user_id_hidden').val(userId);
                $('#vehicle_id_hidden').val(vehicleId);

                // Fetch user and vehicle names for display
                $.when(
                    $.ajax({
                        url: '/user-get-all',
                        method: 'GET'
                    }),
                    $.ajax({
                        url: '/api/vehicles',
                        method: 'GET'
                    })
                ).then((userRes, vehicleRes) => {
                    const user = userRes[0].message?.find(u => u.id == userId);
                    const vehicle = vehicleRes[0].data?.find(v => v.id == vehicleId);

                    $('#user_display').text(user ? `${user.user_name} (${user.email})` :
                        'Unknown User');
                    $('#vehicle_display').text(vehicle ? vehicle.vehicle_number :
                        'Unknown Vehicle');
                }).fail(() => {
                    $('#user_display').text('Error loading');
                    $('#vehicle_display').text('Error loading');
                });

                // Hide real selects, show display divs
                $('#user_id').hide();
                $('#vehicle_id').hide();

                // Set modal title
                $('#modalTitle').text('Assign Another Route');
                $('#saveBtn').text('Assign Route');

                // Clear route selection
                $('#route_id').val('');

                // Load available routes (excluding already assigned ones)
                $.when(
                    $.ajax({
                        url: '/api/sales-reps',
                        method: 'GET'
                    }),
                    $.ajax({
                        url: '/api/routes',
                        method: 'GET'
                    })
                ).then((salesRes, routeRes) => {
                    const assignedRouteIds = salesRes[0].data
                        .filter(g => g.user_id == userId && g.vehicle_id == vehicleId)
                        .flatMap(g => g.routes
                            .filter(r => !r.end_date || new Date(r.end_date) >= new Date())
                            .map(r => r.route_id)
                        );

                    $('#route_id').empty().append('<option value="">Select Route</option>');
                    if (routeRes[0].status && Array.isArray(routeRes[0].data)) {
                        routeRes[0].data
                            .filter(r => r.status === 'active')
                            .filter(r => !assignedRouteIds.includes(r.id))
                            .forEach(r => {
                                $('#route_id').append(
                                    `<option value="${r.id}">${r.name}</option>`
                                );
                            });
                    }
                });

                $('#addAndEditSalesRepModal').modal('show');
            });

            $('#salesRepAddUpdateForm').on('submit', function(e) {
                e.preventDefault();
                const id = $('#sales_rep_id').val();
                const url = id ? `/api/sales-reps/${id}` : `/api/sales-reps`;
                const method = id ? 'PUT' : 'POST';
                const formData = $(this).serialize();

                console.log('FormData:', formData); // ✅ Debugging to verify data

                $.ajax({
                    url: url,
                    method: method,
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(res) {
                        $('#addAndEditSalesRepModal').modal('hide');
                        table.ajax.reload();
                        toastr.success(res.message);
                    },
                    error: function(xhr) {
                        const errors = xhr.responseJSON?.errors || {};
                        $('.text-danger').text('');
                        Object.keys(errors).forEach(key => {
                            $(`#${key}_error`).text(Array.isArray(errors[key]) ? errors[
                                key][0] : errors[key]);
                        });
                        toastr.error(xhr.responseJSON?.message || 'An error occurred.');
                    }
                });
            });

            $('#salesRepsTable').on('click', '.deleteBtn', function() {
                $('#delete_sales_rep_id').val($(this).data('id'));
                $('#deleteModal').modal('show');
            });

            $('.confirm-delete-btn').on('click', function() {
                const id = $('#delete_sales_rep_id').val();
                $.ajax({
                    url: `/api/sales-reps/${id}`,
                    method: 'DELETE',
                    success: function(res) {
                        $('#deleteModal').modal('hide');
                        table.ajax.reload();
                        toastr.success(res.message);
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON?.message || 'Delete failed.');
                    }
                });
            });

            $('#addAndEditSalesRepModal').on('hidden.bs.modal', function() {
                $('#user_id').prop('disabled', false);
                $('#vehicle_id').prop('disabled', false);
            });
        });
    </script>
@endsection
