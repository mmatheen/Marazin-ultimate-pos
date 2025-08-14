@extends('layout.layout')

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <div class="content container-fluid">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Site Settings</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item">Admin</li>
                                <li class="breadcrumb-item active">Settings</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="card card-table">
                    <div class="card-body">
                        <div class="page-header">
                            <div class="row align-items-center">
                                <div class="col-auto text-end float-end ms-auto download-grp">
                                    <button type="button" class="btn btn-outline-info" id="addSettingButton">
                                        <i class="fas fa-plus"></i> New Site
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="setting" class="datatable table table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>App Name</th>
                                        <th>Logo</th>
                                        <th>Favicon</th>
                                        <th>Active</th>
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

        <!-- Add/Edit Modal -->
        <div id="addEditSettingModal" class="modal fade" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="text-center mt-2 mb-4">
                            <h5 id="modalTitle">Add Site</h5>
                        </div>
                        <form id="settingAddAndUpdateForm" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="edit_id" id="edit_id">

                            <div class="mb-3">
                                <label>App Name <span class="text-danger">*</span></label>
                                <input class="form-control" id="edit_app_name" name="app_name" type="text" required>
                                <span class="text-danger" id="app_name_error"></span>
                            </div>

                            <div class="mb-3">
                                <label>Logo</label>
                                <input class="form-control" id="edit_logo" name="logo" type="file" accept="image/*">
                                <span class="text-danger" id="logo_error"></span>
                                <div id="logo_preview" class="mt-2"></div>
                            </div>

                            <div class="mb-3">
                                <label>Favicon</label>
                                <input class="form-control" id="edit_favicon" name="favicon" type="file"
                                    accept="image/*">
                                <span class="text-danger" id="favicon_error"></span>
                                <div id="favicon_preview" class="mt-2"></div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active"
                                        value="1">
                                    <label class="form-check-label" for="edit_is_active">Set as Active Site</label>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="submit" id="modalButton" class="btn btn-outline-primary">Save</button>
                                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Modal -->
        <div id="deleteModal" class="modal fade" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body text-center">
                        <h3>Delete Site</h3>
                        <p>Are you sure you want to delete this site?</p>
                        <input type="hidden" id="deleting_id">
                        <button type="button" class="btn btn-primary confirm_delete_btn">Delete</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script>
        $(document).ready(function() {
            console.log('Document ready fired'); // Debug log
            const csrfToken = $('meta[name="csrf-token"]').attr('content');
            console.log('CSRF Token:', csrfToken); // Debug log

            // Initialize DataTable with proper check
            let table;
            if ($.fn.DataTable.isDataTable('#setting')) {
                table = $('#setting').DataTable();
            } else {
                table = $('#setting').DataTable({
                    "paging": true,
                    "searching": true,
                    "ordering": true,
                    "destroy": true
                });
            }

            function resetForm() {
                $('#settingAddAndUpdateForm')[0].reset();
                $('#edit_id').val('');
                $('#modalTitle').text('Add Site');
                $('#modalButton').text('Save');
                $('#app_name_error, #logo_error, #favicon_error').text('');
                $('#logo_preview, #favicon_preview').empty();
            }

            $('#addEditSettingModal').on('hidden.bs.modal', resetForm);

            $('#addSettingButton').click(function() {
                console.log('Add Setting Button clicked!'); // Debug log
                resetForm();
                $('#addEditSettingModal').modal('show');
            });

            function loadSettings() {
                $.ajax({
                    url: '/site-settings/all',
                    type: 'GET',
                    success: function(res) {
                        table.clear();
                        let counter = 1;
                        res.data.forEach(s => {
                            const logo = s.logo ?
                                `<img src="${s.logo_url}" width="50" alt="Logo">` : 'No Logo';
                            const favicon = s.favicon ?
                                `<img src="${s.favicon_url}" width="20" alt="Favicon">` :
                                'No Favicon';

                            const activeRadio = `
                        <div class="form-check">
                            <input class="form-check-input set-active" type="radio" name="is_active" value="${s.id}" ${s.is_active ? 'checked' : ''}>
                            <label class="form-check-label"></label>
                        </div>
                    `;

                            const actions = `
                        <button class="edit_btn btn btn-outline-info btn-sm me-2" value="${s.id}"><i class="feather-edit"></i> Edit</button>
                        <button class="delete_btn btn btn-outline-danger btn-sm" value="${s.id}"><i class="feather-trash-2"></i> Delete</button>
                    `;

                            table.row.add([
                                counter++,
                                s.app_name,
                                logo,
                                favicon,
                                activeRadio,
                                actions
                            ]).draw(false);
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading settings:', error);
                        toastr.error('Failed to load settings.', 'Error');
                    }
                });
            }

            loadSettings();

            // Set Active Site
            $(document).on('change', '.set-active', function() {
                const id = $(this).val();
                const appName = $(this).closest('tr').find('td:eq(1)').text().trim();

                $.ajax({
                    url: `/site-settings/update/${id}`,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    data: {
                        is_active: 1,
                        app_name: appName
                    },
                    success: function(res) {
                        if (res.status) {
                            toastr.success('Active site updated!', 'Success');
                            loadSettings();
                        } else {
                            toastr.error('Failed to set active site.', 'Error');
                        }
                    },
                    error: function() {
                        toastr.error('Failed to set active site.', 'Error');
                    }
                });
            });

            // Edit
            $(document).on('click', '.edit_btn', function() {
                const id = $(this).val();
                resetForm();
                $('#edit_id').val(id);
                $('#modalTitle').text('Edit Site');
                $('#modalButton').text('Update');

                $.get(`/site-settings/${id}`, function(res) {
                    if (res.status) {
                        const s = res.data;
                        $('#edit_app_name').val(s.app_name);
                        $('#edit_is_active').prop('checked', s.is_active);

                        if (s.logo) {
                            $('#logo_preview').html(
                                `<img src="${s.logo_url}" width="60" class="mt-2">`);
                        }
                        if (s.favicon) {
                            $('#favicon_preview').html(
                                `<img src="${s.favicon_url}" width="30" class="mt-2">`);
                        }
                        $('#addEditSettingModal').modal('show');
                    }
                });
            });

            // Save / Update
            $('#settingAddAndUpdateForm').submit(function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const id = $('#edit_id').val();
                const url = id ? `/site-settings/update/${id}` : '/site-settings/store';
                const method = 'POST';

                $.ajax({
                    url: url,
                    method: method,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(res) {
                        if (res.status) {
                            $('#addEditSettingModal').modal('hide');
                            loadSettings();
                            toastr.success(res.message);
                            resetForm();
                        } else {
                            toastr.error('Operation failed.', 'Error');
                        }
                    },
                    error: function(xhr) {
                        const err = xhr.responseJSON?.errors || {};
                        $.each(err, (k, v) => $(`#${k}_error`).text(v[0]));
                        toastr.error('Please correct the errors.', 'Validation Error');
                    }
                });
            });

            // Delete
            $(document).on('click', '.delete_btn', function() {
                $('#deleting_id').val($(this).val());
                $('#deleteModal').modal('show');
            });

            $('.confirm_delete_btn').click(function() {
                const id = $('#deleting_id').val();
                $.ajax({
                    url: `/site-settings/delete/${id}`,
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    success: function(res) {
                        $('#deleteModal').modal('hide');
                        if (res.status) {
                            loadSettings();
                            toastr.success(res.message);
                        } else {
                            toastr.error(res.message);
                        }
                    },
                    error: function() {
                        toastr.error('An error occurred.', 'Error');
                    }
                });
            });
        });
    </script>
@endsection
