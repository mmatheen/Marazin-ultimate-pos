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
                                    @can('create city')
                                    <button type="button" class="btn btn-outline-info" id="addCityButton">
                                        New <i class="fas fa-plus px-2"></i>
                                    </button>
                                    @endcan
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
                                        @if(auth()->user()->can('edit city') || auth()->user()->can('delete city'))
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

    @include('contact.customer.city_modal')

    @can('delete city')
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
    @endcan

    <script>
        window.canEditCity = @json(auth()->user()->can('edit city'));
        window.canDeleteCity = @json(auth()->user()->can('delete city'));

        $(document).ready(function() {
            if ($.fn.DataTable.isDataTable('#citiesTable')) {
                $('#citiesTable').DataTable().destroy();
            }

            const hasActionColumn = window.canEditCity || window.canDeleteCity;

            const columns = [{
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
            ];

            if (hasActionColumn) {
                columns.push({
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function(data) {
                        let buttons = '';
                        if (window.canEditCity) {
                            buttons += `<button class='btn btn-sm btn-info editBtn' data-id='${data.id}'>Edit</button> `;
                        }
                        if (window.canDeleteCity) {
                            buttons += `<button class='btn btn-sm btn-danger deleteBtn' data-id='${data.id}'>Delete</button>`;
                        }
                        return buttons.trim() || '—';
                    }
                });
            }

            const table = $('#citiesTable').DataTable({
                processing: false,
                serverSide: false,
                ajax: {
                    url: "{{ url('/api/cities') }}",
                    type: "GET",
                    dataSrc: "data",
                    error: function(xhr) {
                        console.log('Error loading cities:', xhr);
                        return [];
                    }
                },
                language: {
                    emptyTable: "No cities found",
                    zeroRecords: "No cities found",
                    loadingRecords: "",
                    processing: ""
                },
                columns: columns
            });

            @can('edit city')
            $('#citiesTable').on('click', '.editBtn', function() {
                const id = $(this).data('id');
                $.get(`/api/cities/${id}`, function(response) {
                    if (response.status === true && response.data) {
                        if (window.populateCityEditForm) {
                            window.populateCityEditForm(response.data);
                            $('#addAndEditCityModal').modal('show');
                        }
                    } else {
                        toastr.error('Failed to load city data.');
                    }
                }).fail(function() {
                    toastr.error('City not found or server error.');
                });
            });
            @endcan

            @can('delete city')
            $('#citiesTable').on('click', '.deleteBtn', function() {
                const id = $(this).data('id');
                $('#delete_city_id').val(id);
                $('#deleteModal').modal('show');
            });

            $('.confirm-delete-btn').on('click', function() {
                const id = $('#delete_city_id').val();

                $.ajax({
                    url: `/api/cities/${id}`,
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
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
            @endcan
        });
    </script>

    @include('contact.customer.cities_ajax')
@endsection
