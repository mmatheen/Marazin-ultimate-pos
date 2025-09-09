@extends('layout.layout')
@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Cities</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item">Cities</li>
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
                                    <button type="button" class="btn btn-outline-info" id="addCityButton">
                                        New <i class="fas fa-plus px-2"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- DataTable -->
                        <div class="table-responsive">
                            <table id="citiesTable" class="datatable table table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>City Name</th>
                                        <th>District</th>
                                        <th>Province</th>
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
    <div class="modal fade" id="addAndEditCityModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="text-center mt-2 mb-4">
                        <h5 id="modalTitle">Add City</h5>
                    </div>
                    <form id="cityAddUpdateForm">
                        <input type="hidden" name="id" id="city_id">

                        <div class="mb-3">
                            <label>City Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="city_name" class="form-control"
                                placeholder="e.g. Colombo">
                            <span class="text-danger" id="name_error"></span>
                        </div>

                        <div class="mb-3">
                            <label>Province</label>
                            <select name="province" id="province" class="form-select">
                                <option value="">Select Province</option>
                                <option value="Western">Western</option>
                                <option value="Central">Central</option>
                                <option value="Southern">Southern</option>
                                <option value="North Western">North Western</option>
                                <option value="North Central">North Central</option>
                                <option value="Northern">Northern</option>
                                <option value="Eastern">Eastern</option>
                                <option value="Uva">Uva</option>
                                <option value="Sabaragamuwa">Sabaragamuwa</option>
                            </select>
                            <span class="text-danger" id="province_error"></span>
                        </div>

                        <div class="mb-3">
                            <label>District</label>
                            <select name="district" id="district" class="form-select" disabled>
                                <option value="">Select District</option>
                            </select>
                            <span class="text-danger" id="district_error"></span>
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
                        <h3>Delete City</h3>
                        <p>Are you sure you want to delete this city?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <input type="hidden" id="delete_city_id">
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


            var provinceDistricts = {
                'Western': ['Colombo', 'Gampaha', 'Kalutara'],
                'Central': ['Kandy', 'Matale', 'Nuwara Eliya'],
                'Southern': ['Galle', 'Matara', 'Hambantota'],
                'North Western': ['Kurunegala', 'Puttalam'],
                'North Central': ['Anuradhapura', 'Polonnaruwa'],
                'Northern': ['Jaffna', 'Kilinochchi', 'Mullaitivu'],
                'Eastern': ['Ampara', 'Batticaloa', 'Trincomalee'],
                'Uva': ['Badulla', 'Monaragala'],
                'Sabaragamuwa': ['Kegalle', 'Ratnapura']
            };

            // Helper: populate District dropdown based on Province selection
            function populateDistricts(province, selectedDistrict = '') {
                const $district = $('#district');
                $district.empty();

                if (province && provinceDistricts[province]) {
                    $district.prop('disabled', false);
                    $district.append('<option value="">Select District</option>');
                    provinceDistricts[province].forEach(function(district) {
                        const selected = district === selectedDistrict ? 'selected' : '';
                        $district.append(`<option value="${district}" ${selected}>${district}</option>`);
                    });
                } else {
                    $district.prop('disabled', true);
                    $district.append('<option value="">Select District</option>');
                }
            }


            // --- Province change in modal: update district dropdown ---
            $('#province').on('change', function() {
                populateDistricts($(this).val());
            });


            // --- Prevent DataTable Reinitialization ---
            if ($.fn.DataTable.isDataTable('#citiesTable')) {
                $('#citiesTable').DataTable().destroy();
            }

            // --- Initialize DataTable ---
            const table = $('#citiesTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: "{{ url('/api/cities') }}",
                    type: "GET",
                    dataSrc: "data",
                    error: function(xhr) {
                        let message = 'Failed to load cities.';
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
                        data: 'name'
                    },
                    {
                        data: 'district',
                        render: function(data) {
                            return data ? data : '—';
                        }
                    },
                    {
                        data: 'province',
                        render: function(data) {
                            return data ? data : '—';
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
            $('#addCityButton').on('click', function() {
                $('#cityAddUpdateForm')[0].reset();
                $('#city_id').val('');
                $('#modalTitle').text('Add City');
                $('#saveBtn').text('Save');
                $('.text-danger').text('');
                $('#addAndEditCityModal').modal('show');
            });

            // --- Save or Update City ---
            $('#cityAddUpdateForm').on('submit', function(e) {
                e.preventDefault();

                const id = $('#city_id').val();
                const url = id ? `/api/cities/${id}` : `/api/cities`;
                const method = id ? 'PUT' : 'POST';

                $.ajax({
                    url: url,
                    method: method,
                    data: $(this).serialize(),
                    success: function(response) {
                        $('#addAndEditCityModal').modal('hide');
                        table.ajax.reload();
                        toastr.success(response.message || 'City saved successfully.');
                    },
                    error: function(xhr) {
                        const errors = xhr.responseJSON?.errors || {};
                        const message = xhr.responseJSON?.message || 'An error occurred.';

                        $('#name_error').text(errors.name || '');
                        $('#district_error').text(errors.district || '');
                        $('#province_error').text(errors.province || '');

                        toastr.error(message);
                    }
                });
            });

            // --- Edit City ---
            $('#citiesTable').on('click', '.editBtn', function() {
                const id = $(this).data('id');
                $.get(`/api/cities/${id}`, function(response) {
                    if (response.status === true && response.data) {
                        const data = response.data;

                        $('#city_id').val(data.id);
                        $('#city_name').val(data.name);
                        $('#district').val(data.district || '');
                        $('#province').val(data.province || '');

                        $('#modalTitle').text('Edit City');
                        $('#saveBtn').text('Update');
                        $('#addAndEditCityModal').modal('show');

                        // Clear previous errors
                        $('.text-danger').text('');
                    } else {
                        toastr.error('Failed to load city data.');
                    }
                }).fail(function() {
                    toastr.error('City not found or server error.');
                });
            });

            // --- Open Delete Modal ---
            $('#citiesTable').on('click', '.deleteBtn', function() {
                const id = $(this).data('id');
                $('#delete_city_id').val(id);
                $('#deleteModal').modal('show');
            });

            // --- Confirm Delete ---
            $('.confirm-delete-btn').on('click', function() {
                const id = $('#delete_city_id').val();

                $.ajax({
                    url: `/api/cities/${id}`,
                    method: 'DELETE',
                    success: function(response) {
                        $('#deleteModal').modal('hide');
                        table.ajax.reload();
                        toastr.success(response.message || 'City deleted successfully.');
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message ||
                            'Failed to delete city.';
                        toastr.error(message);
                    }
                });
            });
        });
    </script>
@endsection
