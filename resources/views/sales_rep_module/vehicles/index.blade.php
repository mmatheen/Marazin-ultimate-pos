@extends('layout.layout')
@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Vehicles</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item">Vehicles</li>
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
                                    {{-- @can('create vehicle') --}}
                                    <button type="button" class="btn btn-outline-info" id="addVehicleButton">
                                        New <i class="fas fa-plus px-2"></i>
                                    </button>
                                    {{-- @endcan --}}
                                </div>
                            </div>
                        </div>

                        <!-- DataTable -->
                        <div class="table-responsive">
                            <table id="vehiclesTable" class="datatable table table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Vehicle Number</th>
                                        <th>Vehicle Type</th>
                                        <th>Description</th>
                                        <th>Location</th>
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
    <div class="modal fade" id="addAndEditVehicleModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="text-center mt-2 mb-4">
                        <h5 id="modalTitle">Add Vehicle</h5>
                    </div>
                    <form id="vehicleAddUpdateForm">
                        <input type="hidden" name="id" id="vehicle_id">

                        <div class="mb-3">
                            <label>Vehicle Number <span class="text-danger">*</span></label>
                            <input type="text" name="vehicle_number" id="vehicle_number" class="form-control"
                                placeholder="e.g. ABC-123">
                            <span class="text-danger" id="vehicle_number_error"></span>
                        </div>

                        <div class="mb-3">
                            <label>Vehicle Type <span class="text-danger">*</span></label>
                            <select name="vehicle_type" id="vehicle_type" class="form-control">
                                <option value="">Select Type</option>
                                <option value="bike">Bike</option>
                                <option value="van">Van</option>
                                <option value="other">Other</option>
                            </select>
                            <span class="text-danger" id="vehicle_type_error"></span>
                        </div>

                        <div class="mb-3">
                            <label>Description</label>
                            <textarea name="description" id="description" class="form-control" rows="3" placeholder="Optional details"></textarea>
                        </div>

                        <div class="mb-3">
                            <label>Location <span class="text-danger">*</span></label>
                            <select name="location_id" id="location_id" class="form-control">
                                <option value="">Select Location</option>
                            </select>
                            <span class="text-danger" id="location_id_error"></span>
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
                        <h3>Delete Vehicle</h3>
                        <p>Are you sure you want to delete this vehicle?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <input type="hidden" id="delete_vehicle_id">
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
            if ($.fn.DataTable.isDataTable('#vehiclesTable')) {
                $('#vehiclesTable').DataTable().destroy();
            }

            // --- Initialize DataTable ---
            const table = $('#vehiclesTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: "{{ url('/api/vehicles') }}",
                    type: "GET",
                    dataSrc: "data", // Critical: your API returns { status, message, data }
                    error: function(xhr) {
                        let message = 'Failed to load vehicles.';
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
                        data: 'vehicle_number'
                    },
                    {
                        data: 'vehicle_type',
                        render: function(data) {
                            return data ? data.charAt(0).toUpperCase() + data.slice(1) : '—';
                        }
                    },
                    {
                        data: 'description',
                        render: function(data) {
                            return data ? data : '—';
                        }
                    },

                    {
                        data: null,
                        render: function(data) {
                            if (data.location) {
                                return `<strong>${data.location.name}</strong><br>
                    <small class="text-muted">${data.location.parent_name}</small>`;
                            }
                            return '<span class="badge bg-warning">No Location</span>';
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function(data) {
                            return `
                            <button class='btn btn-sm btn-info editBtn' data-id='${data.id}'>Edit</button>
                            <button class='btn btn-sm btn-danger deleteBtn' data-id='${data.id}'>Delete</button>
                        `;
                        }
                    }
                ]
            });

            $('#addVehicleButton').on('click', function() {
                $('#vehicleAddUpdateForm')[0].reset();
                $('#vehicle_id').val('');
                $('#modalTitle').text('Add Vehicle');
                $('#saveBtn').text('Save');
                $('#vehicle_number_error').text('');
                $('#vehicle_type_error').text('');
                $('#location_id_error').text('');
                $('#description').val('');

                fetchSubLocations(); // Load options
                $('#addAndEditVehicleModal').modal('show');
            });
            // --- Save or Update Vehicle ---
            $('#vehicleAddUpdateForm').on('submit', function(e) {
                e.preventDefault();

                const id = $('#vehicle_id').val();
                const url = id ? `/api/vehicles/${id}` : `/api/vehicles`;
                const method = id ? 'PUT' : 'POST';

                $.ajax({
                    url: url,
                    method: method,
                    data: $(this).serialize(),
                    success: function(response) {
                        $('#addAndEditVehicleModal').modal('hide');
                        table.ajax.reload();
                        toastr.success(response.message || 'Vehicle saved successfully.');
                    },
                    error: function(xhr) {
                        const errors = xhr.responseJSON?.errors || {};
                        const message = xhr.responseJSON?.message || 'An error occurred.';

                        $('#vehicle_number_error').text(errors.vehicle_number || '');
                        $('#vehicle_type_error').text(errors.vehicle_type || '');
                        $('#location_id_error').text(errors.location_id || ''); // Add this

                        toastr.error(message);
                    }
                });
            });

            function fetchSubLocations() {
                return $.ajax({
                    url: '/location-get-all',
                    type: 'GET',
                    success: function(response) {
                        const select = $('#location_id');
                        select.empty().append('<option value="">Select Sub-Location</option>');

                        if (response.status === true && Array.isArray(response.data)) {
                            let found = false;
                            response.data.forEach(function(loc) {
                                if (loc.parent_id !== null && loc.parent) {
                                    select.append(
                                        `<option value="${loc.id}">${loc.parent.name} → ${loc.name}</option>`
                                    );
                                    found = true;
                                }
                            });
                            if (!found) {
                                select.append(
                                    '<option value="" disabled>No sub-locations available</option>');
                            }
                        } else {
                            select.append('<option value="" disabled>No locations found</option>');
                        }
                    },
                    error: function(xhr) {
                        $('#location_id').html('<option value="">Failed to load locations</option>');
                        toastr.error('Could not load sub-locations.');
                    }
                });
            }

            // --- Edit Vehicle ---
            $('#vehiclesTable').on('click', '.editBtn', function() {
                const id = $(this).data('id');
                $.get(`/api/vehicles/${id}`, function(response) {
                    if (response.status === true && response.data) {
                        const data = response.data;

                        $('#vehicle_id').val(data.id);
                        $('#vehicle_number').val(data.vehicle_number);
                        $('#vehicle_type').val(data.vehicle_type.toLowerCase());
                        $('#description').val(data.description || '');

                        // Load locations first, then set selected
                        fetchSubLocations().then(() => {
                            if (data.location_id) {
                                $('#location_id').val(data.location_id);
                            }
                        });

                        $('#modalTitle').text('Edit Vehicle');
                        $('#saveBtn').text('Update');
                        $('#addAndEditVehicleModal').modal('show');

                        $('#vehicle_number_error').text('');
                        $('#vehicle_type_error').text('');
                        $('#location_id_error').text('');
                    } else {
                        toastr.error('Failed to load vehicle data.');
                    }
                }).fail(function() {
                    toastr.error('Vehicle not found or server error.');
                });
            });
            // --- Open Delete Modal ---
            $('#vehiclesTable').on('click', '.deleteBtn', function() {
                const id = $(this).data('id');
                $('#delete_vehicle_id').val(id);
                $('#deleteModal').modal('show');
            });

            // --- Confirm Delete ---
            $('.confirm-delete-btn').on('click', function() {
                const id = $('#delete_vehicle_id').val();

                $.ajax({
                    url: `/api/vehicles/${id}`,
                    method: 'DELETE',
                    success: function(response) {
                        $('#deleteModal').modal('hide');
                        table.ajax.reload();
                        toastr.success(response.message || 'Vehicle deleted successfully.');
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message ||
                            'Failed to delete vehicle.';
                        toastr.error(message);
                    }
                });
            });
        });
    </script>
@endsection
