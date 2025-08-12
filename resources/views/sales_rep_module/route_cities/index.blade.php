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

        .badge-pill {
            border-radius: 50rem !important;
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
                                    <h4 class="card-title">Routes & Assigned Cities</h4>
                                </div>
                                <div class="col-auto text-end float-end ms-auto download-grp">
                                    <button type="button" class="btn btn-primary" id="addAssignmentBtn">
                                        <i class="fas fa-plus"></i> Assign Cities
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- DataTable -->
                        <div class="table-responsive">
                            <table id="routeCitiesTable" class="datatable table table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Route</th>
                                        <th>Cities</th>
                                        <th>City Count</th>
                                        <th>Last Updated</th>
                                        <th>Actions</th>
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
    <div class="modal fade" id="routeCityModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Assign Cities to Route</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="routeCityForm">
                        @csrf
                        <input type="hidden" name="route_id" id="route_id">

                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label class="form-label">Select Route <span class="text-danger">*</span></label>
                                <select class="form-select" id="route_select" required>
                                    <option value="">Choose a route...</option>
                                </select>
                            </div>
                            {{-- <div class="col-md-4 d-flex align-items-end">
                                <button type="button" class="btn btn-outline-success btn-sm" id="createRouteBtn">
                                    <i class="fas fa-plus"></i> New Route
                                </button>
                            </div> --}}
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label class="form-label">Filter by Province</label>
                                <select class="form-select" id="province_filter">
                                    <option value="">All Provinces</option>
                                </select>
                            </div>
                            {{-- <div class="col-md-4 d-flex align-items-end">
                                <button type="button" class="btn btn-outline-success btn-sm" id="createCityBtn">
                                    <i class="fas fa-plus"></i> New City
                                </button>
                            </div> --}}
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
                                    style="max-height: 300px; overflow-y: auto;"></div>
                                <div class="invalid-feedback d-block" id="cities_error"></div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times"></i>
                        Cancel</button>
                    <button type="button" id="saveBtn" class="btn btn-primary"><i class="fas fa-save"></i> Save
                        Assignment</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Route Modal -->
    <div class="modal fade" id="createRouteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5>Create New Route</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="text" id="new_route_name" class="form-control" placeholder="Route Name">
                    <div class="text-danger mt-1" id="route_error"></div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary" id="saveNewRoute">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create City Modal -->
    <div class="modal fade" id="createCityModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5>Create New City</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2"><input type="text" id="new_city_name" class="form-control"
                            placeholder="City Name"></div>
                    <div class="mb-2"><input type="text" id="new_city_district" class="form-control"
                            placeholder="District (Optional)"></div>
                    <div class="mb-2"><input type="text" id="new_city_province" class="form-control"
                            placeholder="Province"></div>
                    <div class="text-danger mt-1" id="city_error"></div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary" id="saveNewCity">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body text-center mt-3">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <h5>Remove City Assignment?</h5>
                    <p>This will remove the selected city from the route.</p>
                    <input type="hidden" id="delete_assignment_id">
                </div>
                <div class="modal-footer justify-content-center">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="d-none">
        <div class="spinner-border text-primary" role="status"></div>
    </div>
    </div>

    <script>
        $(document).ready(function() {
            let table;
            let allCities = [];
            let allRoutes = [];

            function showLoading() {
                $('#loadingOverlay').removeClass('d-none');
            }

            function hideLoading() {
                $('#loadingOverlay').addClass('d-none');
            }

            // Initialize DataTable
            function initializeDataTable() {
                if ($.fn.DataTable.isDataTable('#routeCitiesTable')) {
                    $('#routeCitiesTable').DataTable().destroy();
                }
                table = $('#routeCitiesTable').DataTable({
                    processing: true,
                    serverSide: false,
                    ajax: {
                        url: "{{ url('/api/route-cities') }}",
                        dataSrc: "data"
                    },
                    columns: [{
                            data: 'route_name',
                            render: (data) => `<strong>${data}</strong>`
                        },
                        {
                            data: 'cities',
                            render: function(cities) {
                                if (!Array.isArray(cities)) return '';
                                return cities.map(c =>
                                    `<span class="badge bg-secondary me-1 mb-1 badge-pill">${c.name}</span>`
                                ).join('');
                            }
                        },
                        {
                            data: 'city_count',
                            render: (data) => `<span class="badge bg-info">${data}</span>`
                        },
                        {
                            data: 'updated_at',
                            render: (data) => new Date(data).toLocaleDateString()
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            render: (data) => `
                        <button class="btn btn-sm btn-outline-info editBtn" data-id="${data.id}"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-outline-danger deleteBtn" data-id="${data.id}"><i class="fas fa-trash"></i></button>
                    `
                        }
                    ]
                });
            }

            // Load Data
            function loadRoutes() {
                $.get("{{ url('/api/routes') }}", function(res) {
                    if (res.status) {
                        // Only keep active routes
                        allRoutes = res.data.filter(r => r.status === 'active');
                        updateRouteDropdown();
                    }
                });
            }

            function loadCities() {
                $.get("{{ url('/api/route-cities/cities/all') }}", function(res) {
                    if (res.status) {
                        allCities = res.data;
                        updateProvinceDropdown();
                        renderCities();
                    }
                });
            }

            function updateRouteDropdown(selectedId = '') {
                let opts = '<option value="">Choose a route...</option>';
                allRoutes.forEach(r => {
                    const sel = r.id == selectedId ? 'selected' : '';
                    opts += `<option value="${r.id}" ${sel}>${r.name}</option>`;
                });
                $('#route_select').html(opts);

                // If a selectedId is provided, set the dropdown value
                if (selectedId) {
                    $('#route_select').val(selectedId);
                }
            }

            function updateProvinceDropdown() {
                const provinces = [...new Set(allCities.map(c => c.province).filter(p => p))];
                let opts = '<option value="">All Provinces</option>';
                provinces.forEach(p => opts += `<option value="${p}">${p}</option>`);
                $('#province_filter').html(opts);
            }

            function renderCities(province = '', selectedIds = []) {
                const filtered = province ? allCities.filter(c => c.province === province) : allCities;
                let html = '';
                filtered.forEach(c => {
                    const isSelected = selectedIds.includes(c.id);
                    const cls = isSelected ? 'selected' : '';
                    html += `
                <div class="city-item ${cls}" data-city-id="${c.id}">
                    <div class="city-name">${c.name}</div>
                    <div class="city-location">${c.district || 'N/A'}, ${c.province}</div>
                </div>
            `;
                });
                $('#cityGrid').html(html);
                updateSelectedCount();
            }

            function updateSelectedCount() {
                $('#selectedCount').text($('.city-item.selected').length);
            }

            // Load on start
            initializeDataTable();
            loadRoutes();
            loadCities();

            // --- Modal Events ---
            $('#addAssignmentBtn').on('click', function() {
                $('#routeCityForm')[0].reset();
                $('#route_id').val('');
                $('#route_select').val('');
                $('#province_filter').val('');
                $('#cities_error').text('');
                renderCities();
                $('#modalTitle').text('Assign Cities to Route');
                $('#saveBtn').html('<i class="fas fa-save"></i> Save Assignment');
                $('#routeCityModal').modal('show');
            });

            $('#route_select').on('change', function() {
                const routeId = $(this).val();
                if (!routeId) {
                    renderCities();
                    return;
                }

                showLoading();
                $.get(`/api/routes/${routeId}/cities`)
                    .done(function(res) {
                        hideLoading();
                        if (res.status && Array.isArray(res.data.cities)) {
                            const selectedIds = res.data.cities.map(c => c.id);
                            renderCities($('#province_filter').val(), selectedIds);
                        } else {
                            renderCities($('#province_filter').val(), []);
                            toastr.warning('No cities assigned yet.');
                        }
                    })
                    .fail(function() {
                        hideLoading();
                        renderCities($('#province_filter').val(), []);
                        toastr.error('Failed to load assigned cities.');
                    });
            });

            // Province Filter
            $('#province_filter').on('change', function() {
                const selectedIds = $('.city-item.selected').map(function() {
                    return parseInt($(this).data('city-id'));
                }).get();
                renderCities($(this).val(), selectedIds);
            });

            // City Selection
            $(document).on('click', '.city-item', function() {
                $(this).toggleClass('selected');
                updateSelectedCount();
            });

            $('#selectAllBtn').on('click', function() {
                $('.city-item').addClass('selected');
                updateSelectedCount();
            });

            $('#deselectAllBtn').on('click', function() {
                $('.city-item').removeClass('selected');
                updateSelectedCount();
            });

            // Save
            $('#saveBtn').on('click', function() {
                const routeId = $('#route_select').val();
                const cityIds = $('.city-item.selected').map(function() {
                    return $(this).data('city-id');
                }).get();

                if (!routeId) return toastr.error('Select a route.');
                if (cityIds.length === 0) return toastr.error('Select at least one city.');

                showLoading();
                $.post('/api/route-cities', {
                    route_id: routeId,
                    city_ids: cityIds,
                    _token: $('meta[name="csrf-token"]').attr('content')
                }, function(res) {
                    hideLoading();
                    if (res.status) {
                        $('#routeCityModal').modal('hide');
                        table.ajax.reload();
                        toastr.success(res.message);
                    } else {
                        toastr.error(res.message);
                    }
                }).fail(function(xhr) {
                    hideLoading();
                    const err = xhr.responseJSON;
                    toastr.error(err.message || 'Failed');
                });
            });

            $('#routeCitiesTable').on('click', '.editBtn', function() {
                const routeId = $(this).data('id'); // This is route.id
                $('#route_select').val(routeId).trigger('change'); // Fetch cities
                $('#route_id').val(routeId);
                $('#modalTitle').text('Edit Route Cities');
                $('#saveBtn').html('<i class="fas fa-save"></i> Update Cities');
                $('#routeCityModal').modal('show');
            });

            // Delete
            $('#routeCitiesTable').on('click', '.deleteBtn', function() {
                $('#delete_assignment_id').val($(this).data('id'));
                $('#deleteModal').modal('show');
            });

            $('#confirmDeleteBtn').on('click', function() {
                const id = $('#delete_assignment_id').val();
                $.ajax({
                    url: `/api/route-cities/${id}`,
                    method: 'DELETE',
                    success: function(res) {
                        $('#deleteModal').modal('hide');
                        table.ajax.reload();
                        toastr.success(res.message);
                    }
                });
            });

            // // Quick Create
            // $('#createRouteBtn').on('click', function() {
            //     $('#createRouteModal').modal('show');
            // });

            // $('#saveNewRoute').on('click', function() {
            //     const name = $('#new_route_name').val();
            //     if (!name) return $('#route_error').text('Name is required');

            //     $.post('{{ url('/routes/quick-create') }}', {
            //         name: name,
            //         _token: $('meta[name="csrf-token"]').attr('content')
            //     }, function(res) {
            //         if (res.status) {
            //             allRoutes.push(res.data);
            //             updateRouteDropdown(res.data.id);
            //             $('#createRouteModal').modal('hide');
            //             $('#new_route_name').val('');
            //             toastr.success('Route created!');
            //         }
            //     });
            // });

            // $('#createCityBtn').on('click', function() {
            //     $('#createCityModal').modal('show');
            // });

            // $('#saveNewCity').on('click', function() {
            //     $.post('{{ url('/cities/quick-create') }}', {
            //         name: $('#new_city_name').val(),
            //         district: $('#new_city_district').val(),
            //         province: $('#new_city_province').val(),
            //         _token: $('meta[name="csrf-token"]').attr('content')
            //     }, function(res) {
            //         if (res.status) {
            //             allCities.push(res.data);
            //             updateProvinceDropdown();
            //             renderCities();
            //             $('#createCityModal').modal('hide');
            //             toastr.success('City created!');
            //         }
            //     });
            // });
        });
    </script>
@endsection
