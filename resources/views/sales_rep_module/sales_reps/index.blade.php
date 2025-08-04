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
                                <tbody>
                                </tbody>
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
                            <label>Vehicle & Location <span class="text-danger">*</span></label>
                            <select name="vehicle_location_id" id="vehicle_location_id" class="form-control" required>
                                <option value="">Select Vehicle and Location</option>
                            </select>
                            <span class="text-danger" id="vehicle_location_id_error"></span>
                            <small class="form-text text-muted">Only vehicles assigned to locations are shown.</small>
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

    <script>
        $(document).ready(function() {
            // --- Prevent DataTable Reinitialization ---
            if ($.fn.DataTable.isDataTable('#salesRepsTable')) {
                $('#salesRepsTable').DataTable().destroy();
            }

            // --- Initialize DataTable ---
            const table = $('#salesRepsTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: "{{ url('/api/sales-reps') }}",
                    type: "GET",
                    dataSrc: "data",
                    error: function(xhr) {
                        let message = 'Failed to load sales representatives.';
                        if (xhr.responseJSON?.message) {
                            message = xhr.responseJSON.message;
                        }
                        toastr.error(message);
                    }
                },
                columns: [{
                        data: 'id'
                    },
                    {
                        data: 'user.user_name',
                        render: function(data) {
                            return data ? data : '—';
                        }
                    },
                    {
                        data: 'user.email',
                        render: function(data) {
                            return data ? data : '—';
                        }
                    },
                    {
                        data: 'vehicle.vehicle_number',
                        render: function(data) {
                            return data ? data : '—';
                        }
                    },
                    {
                        data: 'location.name',
                        render: function(data) {
                            return data ? data : '—';
                        }
                    },
                    {
                        data: 'route.name',
                        render: function(data) {
                            return data ? `<span class="badge bg-info">${data}</span>` :
                                '<span class="text-muted">No Route</span>';
                        }
                    },
                    {
                        data: 'route.cities',
                        render: function(data) {
                            if (!data || data.length === 0) {
                                return '<span class="text-muted">No Cities</span>';
                            }
                            const cityCount = data.length;
                            const firstFew = data.slice(0, 3).map(c => c.name).join(', ');
                            return cityCount <= 3 ?
                                `<span class="text-success">${firstFew}</span>` :
                                `<span class="text-success">${firstFew}</span><br><small>+${cityCount - 3} more</small>`;
                        }
                    },
                    {
                        data: 'status',
                        render: function(data) {
                            const color = data === 'active' ? 'success' : 'secondary';
                            return `<span class="badge bg-${color}">${data}</span>`;
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function(data) {
                            return `
                            <button class='btn btn-sm btn-info editBtn me-1' data-id='${data.id}' title='Edit'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class='btn btn-sm btn-danger deleteBtn' data-id='${data.id}' title='Delete'>
                                <i class="fas fa-trash"></i>
                            </button>
                        `;
                        }
                    }
                ]
            });

            // --- Load Dropdown Data ---
            function loadDropdownData() {
                // Load Users (role = Sales Rep)
                $.get('/user-get-all', function(res) {
                    $('#user_id').empty().append('<option value="">Select User</option>');
                    if (res.message && Array.isArray(res.message)) {
                        res.message.forEach(user => {
                            if (user.role && user.role.toLowerCase() === 'sales rep') {
                                $('#user_id').append(
                                    `<option value="${user.id}">${user.user_name} (${user.email})</option>`
                                );
                            }
                        });
                    }
                }).fail(() => toastr.error('Failed to load users.'));

                // Load Vehicle Locations (with vehicle and location)
                $.get('/api/vehicle-locations', function(res) {
                    $('#vehicle_location_id').empty().append(
                        '<option value="">Select Vehicle and Location</option>');
                    if (res.data && Array.isArray(res.data)) {
                        res.data.forEach(item => {
                            if (item.vehicle && item.location) {
                                $('#vehicle_location_id').append(
                                    `<option value="${item.id}">
                                    ${item.vehicle.vehicle_number} (${item.vehicle.vehicle_type}) - ${item.location.name}
                                </option>`
                                );
                            }
                        });
                    }
                }).fail(() => toastr.error('Failed to load vehicle locations.'));

                // Load Routes
                $.get('{{ url('/api/routes') }}', function(res) {
                    $('#route_id').empty().append('<option value="">Select Route</option>');
                    if (res.data && Array.isArray(res.data)) {
                        res.data.forEach(route => {
                            $('#route_id').append(
                                `<option value="${route.id}">${route.name} (${route.cities_count || route.cities?.length || 0} cities)</option>`
                            );
                        });
                    }
                }).fail(() => toastr.error('Failed to load routes.'));
            }

            // --- Open Add Modal ---
            $('#addSalesRepButton').on('click', function() {
                $('#salesRepAddUpdateForm')[0].reset();
                $('#sales_rep_id').val('');
                $('#modalTitle').text('Add Sales Representative');
                $('#saveBtn').text('Save');
                $('.text-danger').text('');
                loadDropdownData();
                $('#addAndEditSalesRepModal').modal('show');
            });

            // --- Save or Update Sales Rep ---
            $('#salesRepAddUpdateForm').on('submit', function(e) {
                e.preventDefault();
                const id = $('#sales_rep_id').val();
                const url = id ? `{{ url('/api/sales-reps') }}/${id}` : `{{ url('/api/sales-reps') }}`;
                const method = id ? 'PUT' : 'POST';

                $.ajax({
                    url: url,
                    method: method,
                    data: $(this).serialize(),
                    success: function(response) {
                        $('#addAndEditSalesRepModal').modal('hide');
                        table.ajax.reload();
                        toastr.success(response.message ||
                            'Sales representative saved successfully.');
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

            // --- Edit Sales Rep ---
            $('#salesRepsTable').on('click', '.editBtn', function() {
                const id = $(this).data('id');
                loadDropdownData();
                setTimeout(() => {
                    $.get(`{{ url('/api/sales-reps') }}/${id}`, function(res) {
                        if (res.status && res.data) {
                            const data = res.data;
                            $('#sales_rep_id').val(data.id);
                            $('#user_id').val(data.user_id);
                            $('#vehicle_location_id').val(data.vehicle_location_id);
                            $('#route_id').val(data.route_id);
                            $('#assigned_date').val(data.assigned_date);
                            $('#status').val(data.status);
                            $('#modalTitle').text('Edit Sales Representative');
                            $('#saveBtn').text('Update');
                            $('#addAndEditSalesRepModal').modal('show');
                            $('.text-danger').text('');
                        } else {
                            toastr.error('Failed to load sales representative data.');
                        }
                    }).fail(() => toastr.error('Could not fetch sales rep data.'));
                }, 600); // Slight delay to ensure dropdowns load
            });

            // --- Open Delete Modal ---
            $('#salesRepsTable').on('click', '.deleteBtn', function() {
                const id = $(this).data('id');
                $('#delete_sales_rep_id').val(id);
                $('#deleteModal').modal('show');
            });

            // --- Confirm Delete ---
            $('.confirm-delete-btn').on('click', function() {
                const id = $('#delete_sales_rep_id').val();
                $.ajax({
                    url: `{{ url('/api/sales-reps') }}/${id}`,
                    method: 'DELETE',
                    success: function(response) {
                        $('#deleteModal').modal('hide');
                        table.ajax.reload();
                        toastr.success(response.message ||
                            'Sales representative deleted successfully.');
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message ||
                            'Failed to delete sales representative.';
                        toastr.error(message);
                    }
                });
            });
        });
    </script>
@endsection
