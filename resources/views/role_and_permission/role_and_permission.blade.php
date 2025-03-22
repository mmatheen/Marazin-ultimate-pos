@extends('layout.layout')
@section('content')
<div class="content container-fluid">
    <div class="row">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">Add Role</h3>
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
                            <form class="px-3" id="addAndUpdateForm">
                                @csrf <!-- Add CSRF token for form submission -->
                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="form-group local-forms d-flex justify-content-center">
                                            <label>Role Name<span class="login-danger">*</span></label>
                                            <select id="select_role_id" name="role_id" class="form-control form-select">
                                                <option selected disabled>Please Select </option>
                                                @foreach($roles as $role)
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
                                            <a type="button" class="btn btn-outline-info " href="{{ route('group-role-and-permission-view') }}">List All View</a>
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
                                        @foreach($permissionsData as $groupName => $permissions)
                                        <div class="row mb-3">
                                            <div class="col-md-4 mt-1">
                                                <p>{{ $groupName }}</p> <!-- Display permission group name -->
                                            </div>

                                            <div class="col-md-3">
                                                <div class="form-check">
                                                    <!-- "Select All" checkbox with a unique ID for each group -->
                                                    <input class="form-check-input group-select-all" type="checkbox" value="{{ Str::slug($groupName) }}" id="selectGroup{{ Str::slug($groupName) }}">
                                                    <label class="form-check-label mt-1" for="selectGroup{{ Str::slug($groupName) }}">
                                                        Select All
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="col-md-5">
                                                <!-- Loop through the permissions inside each group -->
                                                @foreach($permissions as $permission)
                                                <div class="col">
                                                    <div class="form-check ms-3">
                                                        <!-- Assign a data-group-id to each permission checkbox -->
                                                        <input class="form-check-input" type="checkbox" name="permission_id[]" value="{{ $permission->id }}" id="selectPermission{{ $permission->id }}" data-group-id="{{ Str::slug($groupName) }}">
                                                        <label class="form-check-label mt-1" for="selectPermission{{ $permission->id }}">
                                                            {{ $permission->name }}
                                                        </label>
                                                    </div>
                                                </div>
                                                @endforeach
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

            // Log all selected permissions globally
            if (this.checked) {
                $('input[name="permission[]"]:checked').each(function() {
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
        });
    });
</script>

<style>
    input[type="checkbox"], .form-check-label {
        cursor: pointer;
    }
</style>


@include('role_and_permission.role_and_permission_ajax')
@include('.role.role_ajax')
@endsection
