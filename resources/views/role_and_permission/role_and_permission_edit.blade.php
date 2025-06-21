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
                                                <select id="edit_role_name" name="role_id" class="form-control form-select">
                                                    <option value="" selected disabled>Select Role</option>
                                                    @foreach ($roles as $roleOption)
                                                        <option value="{{ $roleOption->id }}"
                                                            @if ($roleOption->id == $selectedRoleId) selected @endif>
                                                            {{ $roleOption->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
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

                                        <div class="col-md-12 mt-3">
                                            @foreach ($permissionsData as $groupName => $permissions)
                                                <div class="row mb-3">
                                                    <div class="col-md-4 mt-1">
                                                        <p>{{ $groupName }}</p>
                                                    </div>

                                                    <div class="col-md-3">
                                                        <div class="form-check">
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
                                                                    $checked = in_array(
                                                                        $permission->id,
                                                                        $role->permissions->pluck('id')->toArray(),
                                                                    );
                                                                @endphp
                                                                @if ($isOwn || $isAll)
                                                                    <div class="col">
                                                                        <div class="form-check ms-3">
                                                                            <input class="form-check-input" type="radio"
                                                                                name="permission_id[]"
                                                                                value="{{ $permission->id }}"
                                                                                id="selectPermission{{ $permission->id }}"
                                                                                data-group-id="{{ Str::slug($groupName) }}"
                                                                                @if ($checked) checked @endif>
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
                                                                                data-group-id="{{ Str::slug($groupName) }}"
                                                                                @if ($checked) checked @endif>
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
                                                                            data-group-id="{{ Str::slug($groupName) }}"
                                                                            @if (in_array($permission->id, $role->permissions->pluck('id')->toArray())) checked @endif>
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
            $('#selectAllGlobal').on('change', function() {
                $('.form-check-input').not(this).prop('checked', this.checked);
            });

            $('.group-select-all').on('change', function() {
                var groupId = $(this).val();
                $('input[data-group-id="' + groupId + '"]').prop('checked', this.checked);
            });
        });
    </script>

    <style>
        input[type="checkbox"],
        .form-check-label {
            cursor: pointer;
        }
    </style>

    @include('role_and_permission.role_and_permission_ajax')
@endsection
