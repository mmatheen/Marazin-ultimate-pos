@extends('layout.layout')
@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Routes</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item">Routes</li>
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
                                    @can('create route')
                                    <button type="button" class="btn btn-outline-info" id="addRouteButton">
                                        New <i class="fas fa-plus px-2"></i>
                                    </button>
                                    @endcan
                                </div>
                            </div>
                        </div>

                        <!-- DataTable -->
                        <div class="table-responsive">
                            <table id="routesTable" class="datatable table table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Route Name</th>
                                        <th>Description</th>
                                        <th>Status</th>
                                        @if(auth()->user()->can('edit route') || auth()->user()->can('delete route'))
                                        <th>Action</th>
                                        @endif
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

    @canany(['create route', 'edit route'])
    <!-- Add/Edit Modal -->
    <div class="modal fade" id="addAndEditRouteModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="text-center mt-2 mb-4">
                        <h5 id="modalTitle">Add Route</h5>
                    </div>
                    <form id="routeAddUpdateForm">
                        <input type="hidden" name="id" id="route_id">

                        <div class="mb-3">
                            <label>Route Name</label>
                            <input type="text" name="name" id="route_name" class="form-control"
                                placeholder="e.g. Colombo North Route">
                            <span class="text-danger" id="name_error"></span>
                        </div>
                        <div class="mb-3">
                            <label>Description</label>
                            <textarea name="description" id="route_description" class="form-control" placeholder="Enter route description"></textarea>
                        </div>
                        <div class="mb-3">
                            <label>Status</label>
                            <select name="status" id="route_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <span class="text-danger" id="status_error"></span>
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
    @endcanany

    @can('delete route')
    <!-- Delete Modal -->
    <div id="deleteModal" class="modal custom-modal fade" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3>Delete Route</h3>
                        <p>Are you sure you want to delete this route?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <input type="hidden" id="delete_route_id">
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
    @endcan

    <script>
        window.canCreateRoute = @json(auth()->user()->can('create route'));
        window.canEditRoute = @json(auth()->user()->can('edit route'));
        window.canDeleteRoute = @json(auth()->user()->can('delete route'));

        $(document).ready(function() {
            if ($.fn.DataTable.isDataTable('#routesTable')) {
                $('#routesTable').DataTable().destroy();
            }

            const hasActionColumn = window.canEditRoute || window.canDeleteRoute;

            const columns = [{
                    data: 'id'
                },
                {
                    data: 'name',
                    render: function(data) {
                        return data ? data : '—';
                    }
                },
                {
                    data: 'description',
                    render: function(data) {
                        return data ? data : '—';
                    }
                },
                {
                    data: 'status',
                    render: function(data, type, row) {
                        const color = data === 'active' ? 'success' : 'secondary';
                        const label = data === 'active' ? 'Active' : 'Inactive';

                        if (window.canEditRoute) {
                            const nextStatus = data === 'active' ? 'inactive' : 'active';
                            return `
                                <button class="btn btn-sm btn-${color} status-toggle-btn"
                                    data-id="${row.id}"
                                    data-status="${nextStatus}">
                                    ${label}
                                </button>`;
                        }

                        return `<span class="badge bg-${color}">${label}</span>`;
                    }
                },
            ];

            if (hasActionColumn) {
                columns.push({
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function(data) {
                        let buttons = '';
                        if (window.canEditRoute) {
                            buttons += `<button class='btn btn-sm btn-info editBtn' data-id='${data.id}'>Edit</button> `;
                        }
                        if (window.canDeleteRoute) {
                            buttons += `<button class='btn btn-sm btn-danger deleteBtn' data-id='${data.id}'>Delete</button>`;
                        }
                        return buttons.trim() || '—';
                    }
                });
            }

            const table = $('#routesTable').DataTable({
                processing: false,
                serverSide: false,
                ajax: {
                    url: '/api/routes/',
                    type: "GET",
                    dataSrc: "data",
                    error: function(xhr) {
                        console.log('Error loading routes:', xhr);
                        return [];
                    }
                },
                language: {
                    emptyTable: "No routes found",
                    zeroRecords: "No routes found",
                    loadingRecords: "",
                    processing: ""
                },
                columns: columns
            });

            @can('create route')
            $('#addRouteButton').on('click', function() {
                $('#routeAddUpdateForm')[0].reset();
                $('#route_id').val('');
                $('#modalTitle').text('Add Route');
                $('#saveBtn').text('Save');
                $('.text-danger').text('');
                $('#addAndEditRouteModal').modal('show');
            });
            @endcan

            @canany(['create route', 'edit route'])
            $('#routeAddUpdateForm').on('submit', function(e) {
                e.preventDefault();

                const id = $('#route_id').val();
                if (!id && !window.canCreateRoute) {
                    toastr.error('You do not have permission to create routes.');
                    return;
                }
                if (id && !window.canEditRoute) {
                    toastr.error('You do not have permission to edit routes.');
                    return;
                }

                const url = id ? `/api/routes/${id}` : `/api/routes`;
                const method = id ? 'PUT' : 'POST';

                const formData = {
                    name: $('#route_name').val(),
                    description: $('#route_description').val(),
                    status: $('#route_status').val(),
                };

                $.ajax({
                    url: url,
                    method: method,
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        $('#addAndEditRouteModal').modal('hide');
                        table.ajax.reload();
                        toastr.success(response.message || 'Route saved successfully.');
                    },
                    error: function(xhr) {
                        const errors = xhr.responseJSON?.errors || {};
                        const message = xhr.responseJSON?.message || 'An error occurred.';
                        $('#name_error').text(errors.name || '');
                        toastr.error(message);
                    }
                });
            });
            @endcanany

            @can('edit route')
            $('#routesTable').on('click', '.editBtn', function() {
                const id = $(this).data('id');

                $.get(`/api/routes/${id}`, function(response) {
                    if (response.status === true && response.data) {
                        const data = response.data;
                        $('#route_id').val(data.id);
                        $('#route_name').val(data.name || '');
                        $('#route_description').val(data.description || '');
                        $('#route_status').val(data.status || 'active');
                        $('#modalTitle').text('Edit Route');
                        $('#saveBtn').text('Update');
                        $('#addAndEditRouteModal').modal('show');
                        $('.text-danger').text('');
                    } else {
                        toastr.error('Failed to load route data.');
                    }
                }).fail(function() {
                    toastr.error('Route not found or server error.');
                });
            });

            $('#routesTable').on('click', '.status-toggle-btn', function() {
                const id = $(this).data('id');
                const newStatus = $(this).data('status');

                $.ajax({
                    url: `/api/routes/${id}/status`,
                    method: 'PUT',
                    data: { status: newStatus },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        toastr.success(response.message || 'Status updated successfully.');
                        table.ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message || 'Failed to update status.';
                        toastr.error(message);
                    }
                });
            });
            @endcan

            @can('delete route')
            $('#routesTable').on('click', '.deleteBtn', function() {
                const id = $(this).data('id');
                $('#delete_route_id').val(id);
                $('#deleteModal').modal('show');
            });

            $('.confirm-delete-btn').on('click', function() {
                const id = $('#delete_route_id').val();

                $.ajax({
                    url: `/api/routes/${id}`,
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        $('#deleteModal').modal('hide');
                        table.ajax.reload();
                        toastr.success(response.message || 'Route deleted successfully.');
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message ||
                            'Failed to delete route.';
                        toastr.error(message);
                    }
                });
            });
            @endcan
        });
    </script>
@endsection

