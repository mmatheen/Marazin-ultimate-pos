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
                                <li class="breadcrumb-item active">List</li>
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
                                            New <i class="fas fa-plus px-2"></i>
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
                                            <th>Route</th>
                                            <th>Cities</th>
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
                            <h5 id="modalTitle">Add Sales Representative</h5>
                        </div>
                        <form id="salesRepAddUpdateForm">
                            @csrf
                            <input type="hidden" name="id" id="sales_rep_id">

                            <div class="mb-3">
                                <label>User <span class="text-danger">*</span></label>
                                <select name="user_id" id="user_id" class="form-control" required>
                                    <option value="">Select User</option>
                                </select>
                                <span class="text-danger" id="user_id_error"></span>
                            </div>

                            <div class="mb-3">
                                <label>Vehicle <span class="text-danger">*</span></label>
                                <select name="vehicle_id" id="vehicle_id" class="form-control" required>
                                    <option value="">Select Vehicle</option>
                                </select>
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

                            <div class="mb-3">
                                <label>Status</label>
                                <select name="status" id="status" class="form-control">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>

                            <div class="modal-footer">
                                <button type="submit" id="saveBtn" class="btn btn-outline-primary">Save</button>
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
                            <h3>Delete Sales Representative</h3>
                            <p>Are you sure you want to delete this sales representative?</p>
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
            // --- Prevent DataTable Reinitialization ---
            if ($.fn.DataTable.isDataTable('#salesRepsTable')) {
                $('#salesRepsTable').DataTable().destroy();
            }

            // --- Initialize DataTable ---
            // --- Initialize DataTable ---
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
                        let message = 'Failed to load sales representatives.';
                        if (xhr.responseJSON?.message) message = xhr.responseJSON.message;
                        toastr.error(message);
                    }
                },
                columns: [
                    { data: 'user_id' },
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
                                const statusColor = r.status === 'active' ? 'success' : 'secondary';
                                return `
                                    <div class="mb-1">
                                        <span class="badge bg-info">${r.name}</span>
                                        <span class="badge bg-${statusColor}">${r.status === 'active' ? 'Active' : 'Inactive'}</span>
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
                                let cityList = r.cities?.map(c => c.name) || [];
                                let cityDisplay = cityList.length > 3 ?
                                    `${cityList.slice(0, 3).join(', ')} <br><small>+${cityList.length - 3} more</small>` :
                                    cityList.join(', ');
                                return `
                                    <div class="mb-1">
                                        <strong>${r.name}:</strong> 
                                        <span class="text-success">${cityDisplay || '—'}</span>
                                    </div>
                                `;
                            }).join('');
                        }
                    },
                    {
                        data: 'status',
                        render: (data) => {
                            if (!data) {
                                return `<span class="badge bg-secondary">Inactive</span>`;
                            }
                            const color = data === 'active' ? 'success' : 'secondary';
                            return `<span class="badge bg-${color}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>`;
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: (data) => `
                            <button class='btn btn-sm btn-info editBtn me-1' data-id='${data.user_id}' title='Edit'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class='btn btn-sm btn-danger deleteBtn' data-id='${data.user_id}' title='Delete'>
                                <i class="fas fa-trash"></i>
                            </button>
                        `
                    }
                ]
            });



            // --- Load Dropdowns ---
            function loadDropdowns() {
                // Load Users
                $.ajax({
                    url: '/user-get-all',
                    method: 'GET',
                    success: function(res) {
                        $('#user_id').empty().append('<option value="">Select User</option>');
                        if (res.message && Array.isArray(res.message)) {
                            res.message
                                .filter(u => u.role?.toLowerCase() === 'sales rep')
                                .forEach(u => {
                                    $('#user_id').append(
                                        `<option value="${u.id}">${u.user_name} (${u.email})</option>`
                                    );
                                });
                        }
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
                    },
                    error: () => toastr.error('Failed to load vehicles.')
                });

                // Load only active Routes
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

            // --- Open Modal ---
            $('#addSalesRepButton').on('click', function() {
                $('#salesRepAddUpdateForm')[0].reset();
                $('#sales_rep_id').val('');
                $('#modalTitle').text('Add Sales Representative');
                $('#saveBtn').text('Save');
                $('.text-danger').text('');
                loadDropdowns();
                $('#addAndEditSalesRepModal').modal('show');
            });

            // --- Save/Update ---
            $('#salesRepAddUpdateForm').on('submit', function(e) {
                e.preventDefault();
                const id = $('#sales_rep_id').val();
                const url = id ? `/api/sales-reps/${id}` : `/api/sales-reps`;
                const method = id ? 'PUT' : 'POST';

                const formData = $(this).serialize();

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
                        toastr.success(res.message || 'Saved successfully.');
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

            // --- Edit ---
            $('#salesRepsTable').on('click', '.editBtn', function() {
                const id = $(this).data('id');
                // Load dropdowns and only set values after dropdowns are loaded
                $.when(
                    $.ajax({
                        url: '/user-get-all',
                        method: 'GET',
                        success: function(res) {
                            $('#user_id').empty().append(
                                '<option value="">Select User</option>');
                            if (res.message && Array.isArray(res.message)) {
                                res.message
                                    .filter(u => u.role?.toLowerCase() === 'sales rep')
                                    .forEach(u => {
                                        $('#user_id').append(
                                            `<option value="${u.id}">${u.user_name} (${u.email})</option>`
                                        );
                                    });
                            }
                        }
                    }),
                    $.ajax({
                        url: '/api/vehicles',
                        method: 'GET',
                        success: function(res) {
                            $('#vehicle_id').empty().append(
                                '<option value="">Select Vehicle</option>');
                            if (res.status && Array.isArray(res.data)) {
                                res.data.forEach(v => {
                                    // Use vehicle_number for display, fallback to id if missing
                                    $('#vehicle_id').append(
                                        `<option value="${v.id}">${v.vehicle_number || v.id}</option>`
                                    );
                                });
                            }
                        }
                    }),
                    $.ajax({
                        url: '/api/routes',
                        method: 'GET',
                        success: function(res) {
                            $('#route_id').empty().append(
                                '<option value="">Select Route</option>');
                            if (res.status && Array.isArray(res.data)) {
                                res.data.forEach(r => {
                                    $('#route_id').append(
                                        `<option value="${r.id}">${r.name}</option>`
                                    );
                                });
                            }
                        }
                    })
                ).then(function() {
                    $.get(`/api/sales-reps/${id}`, function(res) {
                        if (res.status && res.data) {
                            const d = res.data;
                            $('#sales_rep_id').val(d.id);
                            $('#user_id').val(d.user_id);
                            $('#vehicle_id').val(d.vehicle_id);
                            $('#route_id').val(d.route_id);
                            // Format assigned_date as yyyy-mm-dd for input[type=date]
                            let assignedDate = d.assigned_date ? d.assigned_date.split('T')[
                                0] : '';
                            $('#assigned_date').val(assignedDate);
                            $('#status').val(d.status);
                            $('#modalTitle').text('Edit Sales Representative');
                            $('#saveBtn').text('Update');
                            $('#addAndEditSalesRepModal').modal('show');
                        } else {
                            toastr.error('Could not load sales rep.');
                        }
                    }).fail(() => toastr.error('Load failed.'));
                });
            });

            // --- Delete ---
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
                        toastr.success(res.message || 'Deleted.');
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON?.message || 'Delete failed.');
                    }
                });
            });
        });
    </script>
@endsection
