@extends('layout.layout')

@section('content')
    <div class="content container-fluid">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Edit Role & Permissions</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item">User Management</li>
                                <li class="breadcrumb-item active">Edit Role</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card card-table">
                    <div class="card-body">
                        <div class="page-header">
                            <div class="row align-items-center">
                                <form class="px-3" id="addAndRoleAndPermissionUpdateForm">
                                    @csrf

                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="form-group local-forms d-flex justify-content-center">
                                                <label>Role Name<span class="login-danger">*</span></label>
                                                <select id="edit_role_name" name="role_id" class="form-control form-select" readonly disabled>
                                                    <option value="{{ $role->id }}" selected>{{ $role->name }}</option>
                                                </select>
                                                <!-- Hidden input to ensure the role_id is still sent with the form -->
                                                <input type="hidden" name="role_id" value="{{ $role->id }}">
                                            </div>
                                        </div>
                                        <div class="col-md-7">
                                            <div class="d-flex justify-content-end">
                                                <a type="button" class="btn btn-outline-info "
                                                    href="{{ route('group-role-and-permission-view') }}">List All View</a>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <div class="form-check ms-3">
                                                <input class="form-check-input" type="checkbox" id="selectAllGlobal">
                                                <label class="form-check-label mt-1" for="selectAllGlobal">
                                                    Select All Permissions
                                                </label>
                                            </div>
                                        </div>
                                        <hr>

                                        @include('role_and_permission.partials.permissions_assign_search')

                                        <div class="col-md-12 mt-3">
                                            @include('role_and_permission.partials.permission_groups_list', [
                                                'selectedPermissionIds' => $role->permissions->pluck('id')->toArray(),
                                            ])
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-outline-primary btn-lg">Update</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Function to check if all permissions in a group are selected
            function checkGroupSelectAll() {
                $('.group-select-all').each(function() {
                    var groupId = $(this).val();
                    var groupCheckboxes = $('input[data-group-id="' + groupId + '"]').not('.group-select-all');
                    var checkedCount = groupCheckboxes.filter(':checked').length;
                    var totalCount = groupCheckboxes.length;
                    
                    // Check the group "Select All" if all permissions in the group are checked
                    $(this).prop('checked', checkedCount === totalCount && totalCount > 0);
                });
                
                // Check global "Select All" if all individual permissions are checked
                var allPermissions = $('input[name="permission_id[]"]');
                var allCheckedPermissions = allPermissions.filter(':checked');
                $('#selectAllGlobal').prop('checked', allPermissions.length === allCheckedPermissions.length && allPermissions.length > 0);
            }

            // Initial check on page load
            checkGroupSelectAll();

            // Global Select All functionality
            $('#selectAllGlobal').on('change', function() {
                var isChecked = this.checked;
                $('input[name="permission_id[]"]').prop('checked', isChecked);
                $('.group-select-all').prop('checked', isChecked);
            });

            // Group Select All functionality
            $('.group-select-all').on('change', function() {
                var groupId = $(this).val();
                var isChecked = this.checked;
                $('input[data-group-id="' + groupId + '"]').not('.group-select-all').prop('checked', isChecked);
                
                // Update global select all
                checkGroupSelectAll();
            });

            // Individual permission checkbox change
            $('input[name="permission_id[]"]').on('change', function() {
                checkGroupSelectAll();
            });
        });
    </script>

    <style>
        input[type="checkbox"],
        .form-check-label {
            cursor: pointer;
        }
    </style>

    @include('role_and_permission.partials.permissions_assign_styles')
    @include('role_and_permission.partials.permissions_assign_filter_script')
    @include('role_and_permission.role_and_permission_ajax')
@endsection
