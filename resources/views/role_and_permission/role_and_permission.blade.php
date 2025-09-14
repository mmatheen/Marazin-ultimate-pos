@extends('layout.layout')
@section('content')
    <div class="content container-fluid">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Add Role & Permissions</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item">User Management</li>
                                <li class="breadcrumb-item active">Add new role</li>
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
                                    @csrf <!-- Add CSRF token for form submission -->
                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="form-group local-forms d-flex justify-content-center">
                                                <label>Role Name<span class="login-danger">*</span></label>
                                                <select id="select_role_id" name="role_id" class="form-control form-select">
                                                    <option selected disabled>Please Select </option>
                                                    @foreach ($roles as $role)
                                                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                                                    @endforeach
                                                </select>

                                                {{-- <button type="button" class="btn btn-outline-info" id="addRoleButton">
                                                <i class="fas fa-plus-circle"></i>
                                            </button> --}}
                                            </div>
                                            <span class="text-danger" id="permission_group_id_error"></span>
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

                                        <div class="col-md-12 mt-3">
                                            <!-- Loop through each permission group -->
                                            @foreach ($permissionsData as $groupName => $permissions)
                                                <div class="row mb-3">
                                                    <div class="col-md-4 mt-1">
                                                        <p>{{ $groupName }}</p> <!-- Display permission group name -->
                                                    </div>

                                                    <div class="col-md-3">
                                                        <div class="form-check">
                                                            <!-- "Select All" checkbox with a unique ID for each group -->
                                                            <input class="form-check-input group-select-all" type="checkbox"
                                                                value="{{ Str::slug($groupName) }}"
                                                                id="selectGroup{{ Str::slug($groupName) }}">
                                                            <label class="form-check-label mt-1"
                                                                for="selectGroup{{ Str::slug($groupName) }}">
                                                                Select All
                                                            </label>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-5">
                                                        <!-- Loop through the permissions inside each group -->
                                                        {{-- @foreach ($permissions as $permission)
                                                <div class="col">
                                                    <div class="form-check ms-3">
                                                        <!-- Assign a data-group-id to each permission checkbox -->
                                                        <input class="form-check-input" type="checkbox" name="permission_id[]" value="{{ $permission->id }}" id="selectPermission{{ $permission->id }}" data-group-id="{{ Str::slug($groupName) }}">
                                                        <label class="form-check-label mt-1" for="selectPermission{{ $permission->id }}">
                                                            {{ $permission->name }}
                                                        </label>
                                                    </div>
                                                </div>
                                                @endforeach --}}

                                                        @php
                                                            // Find if this group has "own" or "All" permissions
                                                            $hasOwn = false;
                                                            $hasAll = false;
                                                            foreach ($permissions as $permission) {
                                                                if (
                                                                    Str::startsWith(
                                                                        strtolower($permission->name),
                                                                        'own',
                                                                    )
                                                                ) {
                                                                    $hasOwn = true;
                                                                }
                                                                if (
                                                                    Str::startsWith(
                                                                        strtolower($permission->name),
                                                                        'all',
                                                                    )
                                                                ) {
                                                                    $hasAll = true;
                                                                }
                                                            }
                                                        @endphp
                                                        
                                                        @if ($hasOwn || $hasAll)
                                                            @foreach ($permissions as $permission)
                                                                @php
                                                                    $isOwn = Str::startsWith(
                                                                        strtolower($permission->name),
                                                                        'own',
                                                                    );
                                                                    $isAll = Str::startsWith(
                                                                        strtolower($permission->name),
                                                                        'all',
                                                                    );
                                                                    $radioName =
                                                                        'permission_radio_' . Str::slug($groupName);
                                                                @endphp
                                                                @if ($isOwn || $isAll)
                                                                    <div class="col">
                                                                        <div class="form-check ms-3">
                                                                            <input class="form-check-input" type="radio"
                                                                                name="permission_id[]"
                                                                                value="{{ $permission->id }}"
                                                                                id="selectPermission{{ $permission->id }}"
                                                                                data-group-id="{{ Str::slug($groupName) }}">
                                                                            <label class="form-check-label mt-1"
                                                                                for="selectPermission{{ $permission->id }}">
                                                                                {{ $permission->name }}
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                @else
                                                                    <div class="col">
                                                                        <div class="form-check ms-3">
                                                                            <input class="form-check-input" type="checkbox"
                                                                                name="permission_id[]"
                                                                                value="{{ $permission->id }}"
                                                                                id="selectPermission{{ $permission->id }}"
                                                                                data-group-id="{{ Str::slug($groupName) }}">
                                                                            <label class="form-check-label mt-1"
                                                                                for="selectPermission{{ $permission->id }}">
                                                                                {{ $permission->name }}
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                        @else
                                                            @foreach ($permissions as $permission)
                                                                <div class="col">
                                                                    <div class="form-check ms-3">
                                                                        <input class="form-check-input" type="checkbox"
                                                                            name="permission_id[]"
                                                                            value="{{ $permission->id }}"
                                                                            id="selectPermission{{ $permission->id }}"
                                                                            data-group-id="{{ Str::slug($groupName) }}">
                                                                        <label class="form-check-label mt-1"
                                                                            for="selectPermission{{ $permission->id }}">
                                                                            {{ $permission->name }}
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        @endif
                                                    </div>
                                                </div>
                                                <hr>
                                            @endforeach
                                        </div>

                                    </div>
                                    <button type="submit" class="btn btn-outline-primary btn-lg">Assign</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <!-- jQuery script to handle checkbox selection -->
    <script>
        $(document).ready(function() {
            // Handle global "Select All" checkbox
            $('#selectAllGlobal').on('change', function() {
                // Toggle all checkboxes based on global checkbox
                $('.form-check-input').not(this).prop('checked', this.checked);
                $('.group-select-all').prop('checked', this.checked);

                // Log all selected permissions globally
                if (this.checked) {
                    $('input[name="permission_id[]"]:checked').each(function() {
                        console.log("Selected Permission (Global):", $(this).val());
                    });
                } else {
                    console.log("All permissions deselected globally");
                }
            });

            // Handle group-specific "Select All" checkboxes
            $('.group-select-all').on('change', function() {
                // Get the unique group ID from the checkbox value
                var groupId = $(this).val();

                // Toggle all permissions within this group
                $('input[data-group-id="' + groupId + '"]').prop('checked', this.checked);

                // Log selected permissions within the group
                if (this.checked) {
                    $('input[data-group-id="' + groupId + '"]:checked').each(function() {
                        console.log("Selected Permission (Group " + groupId + "):", $(this).val());
                    });
                } else {
                    console.log("Permissions deselected in Group " + groupId);
                }
                
                // Update other group checkboxes in case this affects them
                updateGroupCheckboxes();
            });

            // Handle individual permission checkbox changes to update group checkboxes
            $(document).on('change', 'input[name="permission_id[]"]', function() {
                updateGroupCheckboxes();
            });

            // Function to update group "Select All" checkboxes based on individual selections
            function updateGroupCheckboxes() {
                $('.group-select-all').each(function() {
                    var groupId = $(this).val();
                    var totalPermissions = $('input[data-group-id="' + groupId + '"]').length;
                    var checkedPermissions = $('input[data-group-id="' + groupId + '"]:checked').length;
                    
                    // If all permissions in the group are checked, check the group checkbox
                    $(this).prop('checked', totalPermissions === checkedPermissions && totalPermissions > 0);
                });
            }

            // Auto-fetch permissions when role is selected
            $('#select_role_id').on('change', function() {
                var roleId = $(this).val();
                
                // Clear all previously selected permissions
                $('input[name="permission_id[]"]').prop('checked', false);
                $('.group-select-all').prop('checked', false); // Clear group checkboxes too
                
                if (roleId) {
                    // Fetch existing permissions for the selected role
                    $.ajax({
                        url: `/get-role-permissions/${roleId}`,
                        type: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 200) {
                                // Auto-select existing permissions
                                response.role.permissions.forEach(function(permission) {
                                    $(`input[value="${permission.id}"]`).prop('checked', true);
                                });
                                
                                // Update group checkboxes after permissions are loaded
                                updateGroupCheckboxes();
                                
                                // Show info message
                                toastr.info(`Loaded existing permissions for ${response.role.name}`, 'Role Permissions');
                            } else if (response.status === 403 && response.show_toastr) {
                                toastr.error(response.message, 'Permission Denied');
                                document.getElementsByClassName('errorSound')[0].play();
                                // Reset the dropdown
                                $('#select_role_id').val('').trigger('change');
                            }
                        },
                        error: function(xhr) {
                            if (xhr.status === 403) {
                                var errorResponse = JSON.parse(xhr.responseText);
                                if (errorResponse.show_toastr) {
                                    toastr.error(errorResponse.message, 'Permission Denied');
                                    document.getElementsByClassName('errorSound')[0].play();
                                    // Reset the dropdown
                                    $('#select_role_id').val('').trigger('change');
                                }
                            } else {
                                // For new roles or roles without permissions, just continue
                                console.log('No existing permissions or role is new');
                            }
                        }
                    });
                }
            });
        });
    </script>

    <style>
        input[type="checkbox"],
        input[type="radio"],
        .form-check-label {
            cursor: pointer;
        }
        input[type="checkbox"],
        input[type="radio"] {
            width: 20px;
            height: 20px;
            vertical-align: middle;
            margin-top: 0 !important;
            margin-bottom: 0 !important;
            margin-right: 8px;
            margin-left: 0 !important;
        }
        .form-check {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }
        .form-check-label {
            margin-left: 0;
            margin-right: 8px;
            vertical-align: middle;
        }
    </style>


    @include('role_and_permission.role_and_permission_ajax')
    @include('.role.role_ajax')
@endsection
