@extends('layout.layout')
@section('content')
    <style>
        .city-grid {
            background-color: #f8f9fa;
        }

        .city-item {
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 8px 12px;
            margin: 4px;
            background-color: white;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-block;
            min-width: 200px;
        }

        .city-item:hover {
            background-color: #e9ecef;
            border-color: #6c757d;
        }

        .city-item.selected {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
        }

        .city-name {
            font-weight: 500;
            font-size: 0.9em;
        }

        .city-location {
            font-size: 0.8em;
            opacity: 0.8;
        }

        #loadingOverlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            margin-top: 0.5rem;
        }
    </style>
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Route Cities Management</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item">Sales Rep Module</li>
                                <li class="breadcrumb-item">Route Cities</li>
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
                                <div class="col">
                                    <h4 class="card-title">Route-City Assignments</h4>
                                </div>
                                <div class="col-auto text-end float-end ms-auto download-grp">
                                    <button type="button" class="btn btn-primary" id="addAssignmentBtn">
                                        <i class="fas fa-plus"></i> Add Assignment
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- DataTable -->
                        <div class="table-responsive">
                            <table id="routeCitiesTable" class="datatable table table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Route Name</th>
                                        <th>City Name</th>
                                        <th>District</th>
                                        <th>Province</th>
                                        <th>Created Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Filled by DataTable -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="routeCityModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Route-City Assignment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="routeCityForm">
                        @csrf
                        <input type="hidden" name="assignment_id" id="assignment_id">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Select Route <span class="text-danger">*</span></label>
                                    <select class="form-select" name="route_id" id="route_id" required>
                                        <option value="">Choose a route...</option>
                                    </select>
                                    <div class="invalid-feedback" id="route_id_error"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Filter by Province</label>
                                    <select class="form-select" id="province_filter">
                                        <option value="">All Provinces</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label">Select Cities <span class="text-danger">*</span></label>
                            <div class="city-selection-container">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                            id="selectAllBtn">Select All</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                            id="deselectAllBtn">Deselect All</button>
                                    </div>
                                    <div class="badge bg-info"><span id="selectedCount">0</span> cities selected</div>
                                </div>
                                <div id="cityGrid" class="city-grid border rounded p-3"
                                    style="max-height: 300px; overflow-y: auto;">
                                    <!-- Cities loaded here -->
                                </div>
                                <div class="invalid-feedback" id="cities_error"></div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="button" id="saveBtn" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Assignment
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <h4>Are you sure?</h4>
                        <p>Do you want to remove this city assignment from the route?</p>
                        <p class="text-muted">This action cannot be undone.</p>
                    </div>
                    <input type="hidden" id="delete_assignment_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="button" id="confirmDeleteBtn" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete Assignment
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="d-none">
        <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            let table;
            let provinces = [];
            let allCities = [];
            let allRoutes = [];
            let isEditMode = false;

            // Show loading overlay
            function showLoading() {
                $('#loadingOverlay').removeClass('d-none');
            }

            // Hide loading overlay
            function hideLoading() {
                $('#loadingOverlay').addClass('d-none');
            }

        

            // Initialize DataTable
            function initializeDataTable() {
                if ($.fn.DataTable.isDataTable('#routeCitiesTable')) {
                    $('#routeCitiesTable').DataTable().destroy();
                    $('#routeCitiesTable tbody').empty();
                }
                table = $('#routeCitiesTable').DataTable({
                    processing: true,
                    serverSide: false,
                    ajax: {
                        url: "/api/route-cities",
                        dataSrc: function(json) {
                            if (json.status && json.data) {
                                return json.data;
                            }
                            return [];
                        }
                    },
                    columns: [{
                            data: 'id'
                        },
                        {
                            data: 'route_name'
                        },
                        {
                            data: 'city_name'
                        },
                        {
                            data: 'district'
                        },
                        {
                            data: 'province'
                        },
                        {
                            data: 'created_at',
                            render: function(data) {
                                return data ? new Date(data).toLocaleDateString() : '-';
                            }
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            render: function(data) {
                                return `
                            <button class="btn btn-sm btn-outline-info editBtn" data-id="${data.id}" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger deleteBtn" data-id="${data.id}" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        `;
                            }
                        }
                    ],
                    pageLength: 25,
                    responsive: true,
                    order: [
                        [0, 'desc']
                    ],
                    language: {
                        processing: "Loading route-city assignments...",
                        emptyTable: "No route-city assignments found",
                        zeroRecords: "No matching assignments found"
                    }
                });
            }

            // Load dropdown data
            function loadDropdownData() {
                // Load routes
                $.get("/api/route-cities/routes/all")
                    .done(function(response) {
                        if (response.status && response.data) {
                            allRoutes = response.data;
                            updateRouteDropdown();
                        } else {
                            toastr.error(response.message || 'Failed to load routes.');
                        }
                    })
                    .fail(function() {
                        toastr.error('Failed to load routes');
                    });

                // Load cities and provinces
                $.get("/api/route-cities/cities/all")
                    .done(function(response) {
                        if (response.status && response.data) {
                            allCities = response.data;
                            provinces = [...new Set(allCities.map(c => c.province).filter(p => p))];
                            updateProvinceDropdown();
                            renderCities();
                        } else {
                            toastr.error(response.message || 'Failed to load cities.');
                        }
                    })
                    .fail(function() {
                        toastr.error('Failed to load cities.');
                    });
            }

            function updateRouteDropdown(selectedId = '') {
                let options = '<option value="">Choose a route...</option>';
                allRoutes.forEach(route => {
                    const selected = route.id == selectedId ? 'selected' : '';
                    options += `<option value="${route.id}" ${selected}>${route.name}</option>`;
                });
                $('#route_id').html(options);
            }

            function updateProvinceDropdown() {
                let options = '<option value="">All Provinces</option>';
                provinces.forEach(province => {
                    options += `<option value="${province}">${province}</option>`;
                });
                $('#province_filter').html(options);
            }

            function renderCities(provinceFilter = '', selectedCityIds = []) {
                const filteredCities = provinceFilter ?
                    allCities.filter(c => c.province === provinceFilter) :
                    allCities;

                let html = '';
                filteredCities.forEach(city => {
                    const isSelected = selectedCityIds.includes(city.id);
                    const selectedClass = isSelected ? 'selected' : '';
                    html += `
                <div class="city-item ${selectedClass}" data-city-id="${city.id}">
                    <div class="city-name">${city.name}</div>
                    <div class="city-location">${city.district || 'N/A'}, ${city.province || 'N/A'}</div>
                </div>
            `;
                });

                $('#cityGrid').html(html);
                updateSelectedCount();
            }

            function updateSelectedCount() {
                const count = $('.city-item.selected').length;
                $('#selectedCount').text(count);
            }

            // Modal event handlers
            $('#addAssignmentBtn').on('click', function() {
                resetModal();
                isEditMode = false;
                $('#modalTitle').text('Add Route-City Assignment');
                $('#saveBtn').html('<i class="fas fa-save"></i> Save Assignment');
                $('#routeCityModal').modal('show');
            });

            function resetModal() {
                $('#routeCityForm')[0].reset();
                $('#assignment_id').val('');
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('');
                updateRouteDropdown();
                $('#province_filter').val('');
                renderCities();
                isEditMode = false;
            }

            // Province filter
            $('#province_filter').on('change', function() {
                const selectedCityIds = $('.city-item.selected').map(function() {
                    return parseInt($(this).data('city-id'));
                }).get();
                renderCities($(this).val(), selectedCityIds);
            });

            // City selection
            $(document).on('click', '.city-item', function() {
                if (isEditMode) {
                    // In edit mode, deselect all others first
                    $('.city-item').removeClass('selected');
                }
                $(this).toggleClass('selected');
                updateSelectedCount();
            });

            // Select/Deselect all
            $('#selectAllBtn').on('click', function() {
                if (!isEditMode) {
                    $('.city-item').addClass('selected');
                    updateSelectedCount();
                }
            });

            $('#deselectAllBtn').on('click', function() {
                $('.city-item').removeClass('selected');
                updateSelectedCount();
            });

            // Save assignment
            $('#saveBtn').on('click', function() {
                const assignmentId = $('#assignment_id').val();
                const routeId = $('#route_id').val();
                const selectedCityIds = $('.city-item.selected').map(function() {
                    return parseInt($(this).data('city-id'));
                }).get();

                // Clear previous errors
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('');

                // Validation
                if (!routeId) {
                    $('#route_id').addClass('is-invalid');
                    $('#route_id_error').text('Please select a route.');
                    return;
                }

                if (selectedCityIds.length === 0) {
                    $('#cities_error').text('Please select at least one city.');
                    return;
                }

                if (isEditMode && selectedCityIds.length > 1) {
                    $('#cities_error').text('Please select only one city for editing.');
                    return;
                }

                showLoading();

                const url = assignmentId ? `/api/route-cities/${assignmentId}` : '/api/route-cities';
                const method = assignmentId ? 'PUT' : 'POST';
                const data = {
                    route_id: routeId
                };

                if (assignmentId) {
                    data.city_id = selectedCityIds[0]; // For update, single city
                } else {
                    data.city_ids = selectedCityIds; // For create, multiple cities
                }

                $.ajax({
                    url: url,
                    method: method,
                    data: data,
                    success: function(response) {
                        hideLoading();
                        if (response.status) {
                            $('#routeCityModal').modal('hide');
                            table.ajax.reload();
                            toastr.success(response.message);
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        hideLoading();
                        const response = xhr.responseJSON;
                        if (response && response.errors) {
                            if (response.errors.route_id) {
                                $('#route_id').addClass('is-invalid');
                                $('#route_id_error').text(response.errors.route_id[0]);
                            }
                            if (response.errors.city_ids || response.errors.city_id) {
                                $('#cities_error').text(response.errors.city_ids ? response
                                    .errors.city_ids[0] : response.errors.city_id[0]);
                            }
                        } else {
                            toastr.error(response?.message || 'An error occurred');
                        }
                    }
                });
            });

            // Edit assignment
            $('#routeCitiesTable').on('click', '.editBtn', function() {
                const assignmentId = $(this).data('id');

                showLoading();
                $.get(`/api/route-cities/${assignmentId}`)
                    .done(function(response) {
                        hideLoading();
                        if (response.status) {
                            const data = response.data;
                            resetModal();
                            isEditMode = true;
                            $('#modalTitle').text('Edit Route-City Assignment');
                            $('#saveBtn').html('<i class="fas fa-save"></i> Update Assignment');
                            $('#assignment_id').val(data.id);
                            updateRouteDropdown(data.route_id);
                            renderCities('', [data.city_id]);
                            $('#routeCityModal').modal('show');
                        } else {
                            toastr.error(response.message);
                        }
                    })
                    .fail(function() {
                        hideLoading();
                        toastr.error('Failed to load assignment data');
                    });
            });

            // Delete assignment
            $('#routeCitiesTable').on('click', '.deleteBtn', function() {
                $('#delete_assignment_id').val($(this).data('id'));
                $('#deleteModal').modal('show');
            });

            $('#confirmDeleteBtn').on('click', function() {
                const assignmentId = $('#delete_assignment_id').val();

                showLoading();
                $.ajax({
                    url: `/api/route-cities/${assignmentId}`,
                    method: 'DELETE',
                    success: function(response) {
                        hideLoading();
                        if (response.status) {
                            $('#deleteModal').modal('hide');
                            table.ajax.reload();
                            toastr.success(response.message);
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        hideLoading();
                        const response = xhr.responseJSON;
                        toastr.error(response?.message || 'Delete failed');     
                    }
                });
            });

            // Initialize everything
            initializeDataTable();
            loadDropdownData();
        });
    </script>
@endsection
