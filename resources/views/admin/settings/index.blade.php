@extends('layout.layout')

@section('content')
    <div class="content container-fluid">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Settings</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item">Admin</li>
                                <li class="breadcrumb-item active">Settings</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Table Row --}}
        <div class="row">
            <div class="col-sm-12">
                <div class="card card-table">
                    <div class="card-body">
                        <div class="page-header">
                            <div class="row align-items-center">
                                <div class="col-auto text-end float-end ms-auto download-grp">
                                    <button type="button" class="btn btn-outline-info" id="addSettingButton">
                                        New <i class="fas fa-plus px-2"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="setting" class="datatable table table-stripped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>App Name</th>
                                        <th>Logo</th>
                                        <th>Favicon</th>
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

        {{-- Add/Edit Modal --}}
        <div id="addEditSettingModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="text-center mt-2 mb-4">
                            <h5 id="modalTitle">Add Setting</h5>
                        </div>
                        <form id="settingAddAndUpdateForm" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="edit_id" id="edit_id">

                            <div class="mb-3">
                                <label>App Name <span class="text-danger">*</span></label>
                                <input class="form-control" id="edit_app_name" name="app_name" type="text"
                                    placeholder="App Name">
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
                                <label>Status</label>
                                <select class="form-control" name="is_active" id="edit_is_active">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                                <small class="text-muted">Note: Only one setting can be active at a time.</small>
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

        {{-- Delete Modal --}}
        <div id="deleteModal" class="modal custom-modal fade" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="form-header">
                            <h3>Delete Setting</h3>
                            <p>Are you sure you want to delete this setting?</p>
                        </div>
                        <div class="modal-btn delete-action">
                            <div class="row">
                                <input type="hidden" id="deleting_id">
                                <div class="col-6">
                                    <button type="button" class="confirm_delete_btn btn btn-primary"
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
    <!-- Add SweetAlert2 CDN before your script -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            const csrfToken = $('meta[name="csrf-token"]').attr('content');

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

            // Validation rules
            $('#settingAddAndUpdateForm').validate({
                rules: {
                    app_name: {
                        required: true
                    },
                    is_active: {
                        required: true
                    }
                },
                messages: {
                    app_name: {
                        required: "App Name is required"
                    },
                    is_active: {
                        required: "Status is required"
                    }
                },
                errorElement: 'span',
                errorPlacement: function(error, element) {
                    error.addClass('text-danger');
                    error.insertAfter(element);
                },
                highlight: function(element) {
                    $(element).addClass('is-invalid');
                },
                unhighlight: function(element) {
                    $(element).removeClass('is-invalid');
                }
            });

            // Reset form and previews
            function resetForm() {
                $('#settingAddAndUpdateForm')[0].reset();
                $('#edit_id').val('');
                $('#modalTitle').text('Add Setting');
                $('#modalButton').text('Save');
                $('#app_name_error, #logo_error, #favicon_error').text('');
                $('#logo_preview, #favicon_preview').empty();
                $('#settingAddAndUpdateForm').validate().resetForm();
            }

            // Clear form when modal is closed
            $('#addEditSettingModal').on('hidden.bs.modal', function() {
                resetForm();
            });

            // Show Add Modal
            $('#addSettingButton').click(function() {
                resetForm();
                $('#addEditSettingModal').modal('show');
            });

            // Fetch and display settings
            function loadSettings() {
                $.ajax({
                    url: '/site-settings/all',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        table.clear();
                        let counter = 1;
                        response.data.forEach(function(item) {
                            const logoUrl = item.logo ? '{{ Storage::url('settings/') }}' + item
                                .logo : 'N/A';
                            const faviconUrl = item.favicon ?
                                '{{ Storage::url('settings/') }}' + item.favicon : 'N/A';

                            const logoImg = item.logo ?
                                `<img src="${logoUrl}" alt="Logo" width="50">` : 'No Logo';
                            const faviconImg = item.favicon ?
                                `<img src="${faviconUrl}" alt="Favicon" width="20">` :
                                'No Favicon';

                            const statusBadge = item.is_active ?
                                '<span class="badge bg-success">Active</span>' :
                                '<span class="badge bg-secondary">Inactive</span>';

                            const activateBtn = !item.is_active ?
                                `<button type="button" value="${item.id}" class="activate_btn btn btn-outline-success btn-sm me-2">
                                    <i class="feather-check text-success"></i> Activate
                                </button>` : '';

                            const actions = `
                            ${activateBtn}
                            <button type="button" value="${item.id}" class="edit_btn btn btn-outline-info btn-sm me-2">
                                <i class="feather-edit text-info"></i> Edit
                            </button>
                            <button type="button" value="${item.id}" class="delete_btn btn btn-outline-danger btn-sm">
                                <i class="feather-trash-2 text-danger"></i> Delete
                            </button>
                        `;

                            table.row.add([
                                counter++,
                                item.app_name,
                                logoImg,
                                faviconImg,
                                statusBadge,
                                actions
                            ]).draw(false);
                        });
                    },
                    error: function() {
                        toastr.error('Failed to load settings.', 'Error');
                    }
                });
            }

            // Load data on page load
            loadSettings();

            // Show Edit Modal
            $(document).on('click', '.edit_btn', function() {
                const id = $(this).val();
                resetForm();
                $('#edit_id').val(id);
                $('#modalTitle').text('Edit Setting');
                $('#modalButton').text('Update');

                $.ajax({
                    url: `/site-settings/${id}`,
                    type: 'GET',
                    success: function(response) {
                        if (response.status) {
                            const data = response.data;
                            $('#edit_app_name').val(data.app_name);
                            $('#edit_is_active').val(data.is_active ? 1 : 0);

                            // Show logo preview
                            if (data.logo) {
                                const logoUrl = '{{ Storage::url('settings/') }}' + data.logo;
                                $('#logo_preview').html(
                                    `<img src="${logoUrl}" width="60" class="mt-2">`);
                            }

                            // Show favicon preview
                            if (data.favicon) {
                                const faviconUrl = '{{ Storage::url('settings/') }}' + data
                                    .favicon;
                                $('#favicon_preview').html(
                                    `<img src="${faviconUrl}" width="30" class="mt-2">`);
                            }

                            $('#addEditSettingModal').modal('show');
                        }
                    },
                    error: function() {
                        toastr.error('Failed to fetch setting.', 'Error');
                    }
                });
            });

            // Handle form submit (Create/Update)
            $('#settingAddAndUpdateForm').on('submit', function(e) {
                e.preventDefault();

                if (!$(this).valid()) {
                    toastr.warning('Please fix the errors before submitting.', 'Warning');
                    return;
                }

                const formData = new FormData(this);
                const id = $('#edit_id').val();
                const url = id ? `/site-settings/update/${id}` : `/site-settings/store`;
                const method = 'POST';

                $.ajax({
                    url: url,
                    type: method,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    data: formData,
                    contentType: false,
                    processData: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.status) {
                            $('#addEditSettingModal').modal('hide');
                            loadSettings();
                            toastr.success(response.message, 'Success');
                            resetForm();
                        } else {
                            toastr.error('Operation failed.', 'Error');
                        }
                    },
                    error: function(xhr) {
                        const errors = xhr.responseJSON?.errors || {};
                        $.each(errors, function(key, value) {
                            $(`#${key}_error`).text(value[0]);
                        });
                        toastr.error('Please correct the errors.', 'Validation Error');
                    }
                });
            });

            // Delete Setting
            $(document).on('click', '.delete_btn', function() {
                const id = $(this).val();
                $('#deleting_id').val(id);
                $('#deleteModal').modal('show');
            });

            // Activate Setting
            $(document).on('click', '.activate_btn', function() {
                const id = $(this).val();

                Swal.fire({
                    title: 'Are you sure?',
                    text: 'Activating this setting will deactivate all other settings.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, activate it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/site-settings/activate/${id}`,
                            type: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken
                            },
                            success: function(response) {
                                if (response.status) {
                                    loadSettings();
                                    window.location.reload();
                                    toastr.success(response.message, 'Success');
                                } else {
                                    toastr.error(response.message ||
                                        'Activation failed.', 'Error');
                                }
                            },
                            error: function() {
                                toastr.error(
                                    'An error occurred while activating the setting.',
                                    'Error');
                            }
                        });
                    }
                });
            });

            $('.confirm_delete_btn').click(function() {
                const id = $('#deleting_id').val();
                $.ajax({
                    url: `/site-settings/delete/${id}`,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    success: function(response) {
                        $('#deleteModal').modal('hide');
                        if (response.status) {
                            loadSettings();
                            toastr.success(response.message, 'Deleted');
                        } else {
                            toastr.error(response.message || 'Delete failed.', 'Error');
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
