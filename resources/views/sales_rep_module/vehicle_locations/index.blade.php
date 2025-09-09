@extends('layout.layout')
@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Vehicle Locations</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item">Vehicle Locations</li>
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
                                    <button type="button" class="btn btn-outline-info" id="addVehicleLocationButton">
                                        Assign <i class="fas fa-plus px-2"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- DataTable -->
                        <div class="table-responsive">
                            <table id="vehicleLocationsTable" class="datatable table table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Vehicle</th>
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
    <div class="modal fade" id="addAndEditVehicleLocationModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="text-center mt-2 mb-4">
                        <h5 id="modalTitle">Assign Vehicle to Location</h5>
                    </div>
                    <form id="vehicleLocationAddUpdateForm">
                        @csrf
                        <input type="hidden" name="id" id="vehicle_location_id">

                        <div class="mb-3">
                            <label>Vehicle <span class="text-danger">*</span></label>
                            <select name="vehicle_id" id="vehicle_id" class="form-control">
                                <option value="">Loading vehicles...</option>
                            </select>
                            <span class="text-danger" id="vehicle_id_error"></span>
                        </div>

                        <div class="mb-3">
                            <label>Location <span class="text-danger">*</span></label>
                            <select name="location_id" id="location_id" class="form-control">
                                <option value="">Loading locations...</option>
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
                        <h3>Unassign Vehicle Location</h3>
                        <p>Are you sure you want to unassign this vehicle from the location?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <input type="hidden" id="delete_vehicle_location_id">
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
            // Helper: Capitalize first letter
            function ucFirst(str) {
                return str ? str.charAt(0).toUpperCase() + str.slice(1).toLowerCase() : '';
            }

            // --- Fetch Vehicles ---
            function fetchVehicles() {
                $.ajax({
                    url: '/api/vehicles',
                    type: 'GET',
                    success: function(response) {
                        const select = $('#vehicle_id');
                        select.empty().append('<option value="">Select Vehicle</option>');

                        if (response.status === true && Array.isArray(response.data)) {
                            response.data.forEach(function(vehicle) {
                                select.append(`<option value="${vehicle.id}">
                                ${vehicle.vehicle_number} (${ucFirst(vehicle.vehicle_type)})
                            </option>`);
                            });
                        } else {
                            toastr.warning('No vehicles available.');
                        }
                    },
                    error: function(xhr) {
                        $('#vehicle_id').html('<option value="">Failed to load vehicles</option>');
                        toastr.error('Could not load vehicles.');
                    }
                });
            }

            function fetchSubLocations() {
                $.ajax({
                    url: '/location-get-all',
                    type: 'GET',
                    success: function(response) {
                        const select = $('#location_id');
                        select.empty().append('<option value="">Select Sub-Location</option>');

                        if (response.status === true && Array.isArray(response.data)) {
                            let found = false;
                            response.data.forEach(function(loc) {
                                // Only show sub-locations (has parent)
                                if (loc.parent_id !== null && loc.parent) {
                                    select.append(`<option value="${loc.id}">
                                    ${loc.parent.name} → ${loc.name}
                                </option>`);
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
                        $('#location_id').html(
                            '<option value="">Failed to load sub-locations</option>');
                        toastr.error('Could not load sub-locations.');
                    }
                });
            }

            // --- Prevent DataTable Reinitialization ---
            if ($.fn.DataTable.isDataTable('#vehicleLocationsTable')) {
                $('#vehicleLocationsTable').DataTable().destroy();
            }

            // --- Initialize DataTable ---
            const table = $('#vehicleLocationsTable').DataTable({
                processing: false,
                serverSide: false,
                ajax: {
                    url: "/api/vehicle-locations",
                    type: "GET",
                    dataSrc: function(response) {
                        if (response.status === true && Array.isArray(response.data)) {
                            return response.data;
                        } else {
                            return [];
                        }
                    },
                    error: function(xhr) {
                        console.log('Error loading vehicle locations:', xhr);
                        // Don't show toastr error, let table show "No data available"
                        return [];
                    }
                },
                language: {
                    emptyTable: "No vehicle locations found",
                    zeroRecords: "No vehicle locations found",
                    loadingRecords: "",
                    processing: ""
                },
                columns: [{
                        data: 'id'
                    },
                    {
                        data: 'vehicle',
                        render: function(data) {
                            if (data && data.vehicle_number) {
                                return `${data.vehicle_number} (${ucFirst(data.vehicle_type)})`;
                            }
                            return '—';
                        }
                    },
                    {
                        data: 'location',
                        render: function(data) {
                            return data?.name ? `${data.name}` : '—';
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

            // --- Open Add Modal ---
            $('#addVehicleLocationButton').on('click', function() {
                $('#vehicleLocationAddUpdateForm')[0].reset();
                $('#vehicle_location_id').val('');
                $('#modalTitle').text('Assign Vehicle to Location');
                $('#saveBtn').text('Save');
                $('#vehicle_id_error').text('');
                $('#location_id_error').text('');
                $('#addAndEditVehicleLocationModal').modal('show');
            });

            // --- Save or Update ---
            // --- Save or Update ---
            $('#vehicleLocationAddUpdateForm').on('submit', function(e) {
                e.preventDefault();

                // Clear previous errors
                $('#vehicle_id_error').text('');
                $('#location_id_error').text('');

                const id = $('#vehicle_location_id').val();
                const url = id ? `/api/vehicle-locations/${id}` : '/api/vehicle-locations';
                const method = id ? 'PUT' : 'POST';

                const formData = {
                    vehicle_id: $('#vehicle_id').val(),
                    location_id: $('#location_id').val()
                };

                $.ajax({
                    url: url,
                    method: method,
                    data: formData,
                    success: function(response) {
                        $('#addAndEditVehicleLocationModal').modal('hide');
                        table.ajax.reload();
                        toastr.success(response.message ||
                            'Vehicle location saved successfully.');
                    },
                    error: function(xhr) {
                        const errors = xhr.responseJSON?.errors || {};
                        $('#vehicle_id_error').text(errors.vehicle_id?.[0] || '');
                        $('#location_id_error').text(errors.location_id?.[0] || '');
                        toastr.error(xhr.responseJSON?.message ||
                            'An error occurred while saving.');
                    }
                });
            });

            // --- Edit ---
            $('#vehicleLocationsTable').on('click', '.editBtn', function() {
                const id = $(this).data('id');
                $.get(`/api/vehicle-locations/${id}`, function(response) {
                    if (response.status === true && response.data) {
                        const data = response.data;
                        $('#vehicle_location_id').val(data.id);
                        $('#vehicle_id').val(data.vehicle_id);
                        $('#location_id').val(data.location_id);
                        $('#modalTitle').text('Edit Assignment');
                        $('#saveBtn').text('Update');
                        $('#addAndEditVehicleLocationModal').modal('show');
                        $('#vehicle_id_error').text('');
                        $('#location_id_error').text('');
                    } else {
                        toastr.error('Failed to load record.');
                    }
                }).fail(function() {
                    toastr.error('Failed to fetch vehicle location.');
                });
            });

            // --- Open Delete Modal ---
            $('#vehicleLocationsTable').on('click', '.deleteBtn', function() {
                $('#delete_vehicle_location_id').val($(this).data('id'));
                $('#deleteModal').modal('show');
            });

            // --- Confirm Delete ---
            $('.confirm-delete-btn').on('click', function() {
                const id = $('#delete_vehicle_location_id').val();
                $.ajax({
                    url: `/api/vehicle-locations/${id}`,
                    method: 'DELETE',
                    success: function(response) {
                        $('#deleteModal').modal('hide');
                        table.ajax.reload();
                        toastr.success(response.message ||
                            'Vehicle location deleted successfully.');
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON?.message ||
                            'Failed to delete assignment.');
                    }
                });
            });

            // --- Load Vehicles & Locations on Page Load ---
            fetchVehicles();
            fetchSubLocations();
        });
    </script>
@endsection
