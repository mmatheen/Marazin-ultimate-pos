@php
    $selectedPermissionIds = $selectedPermissionIds ?? [];
@endphp

@foreach ($permissionsData as $groupName => $permissions)
    <div class="permission-group-row" data-group-name="{{ strtolower($groupName) }}">
        <div class="row mb-3">
            <div class="col-md-4 mt-1">
                <p class="mb-0">{{ $groupName }}</p>
            </div>

            <div class="col-md-3">
                <div class="form-check">
                    <input class="form-check-input group-select-all" type="checkbox"
                        value="{{ Str::slug($groupName) }}"
                        id="selectGroup{{ Str::slug($groupName) }}">
                    <label class="form-check-label mt-1" for="selectGroup{{ Str::slug($groupName) }}">
                        Select All
                    </label>
                </div>
            </div>

            <div class="col-md-5 permission-items-grid">
                @php
                    $hasOwn = false;
                    $hasAll = false;
                    foreach ($permissions as $permission) {
                        if (Str::startsWith(strtolower($permission->name), 'own')) {
                            $hasOwn = true;
                        }
                        if (Str::startsWith(strtolower($permission->name), 'all')) {
                            $hasAll = true;
                        }
                    }
                @endphp

                @if ($hasOwn || $hasAll)
                    @foreach ($permissions as $permission)
                        @php
                            $isOwn = Str::startsWith(strtolower($permission->name), 'own');
                            $isAll = Str::startsWith(strtolower($permission->name), 'all');
                            $checked = in_array($permission->id, $selectedPermissionIds);
                        @endphp
                        <div class="col permission-item" data-permission-name="{{ strtolower($permission->name) }}">
                            <div class="form-check ms-3">
                                @if ($isOwn || $isAll)
                                    <input class="form-check-input" type="radio" name="permission_id[]"
                                        value="{{ $permission->id }}"
                                        id="selectPermission{{ $permission->id }}"
                                        data-group-id="{{ Str::slug($groupName) }}"
                                        @if ($checked) checked @endif>
                                @else
                                    <input class="form-check-input" type="checkbox" name="permission_id[]"
                                        value="{{ $permission->id }}"
                                        id="selectPermission{{ $permission->id }}"
                                        data-group-id="{{ Str::slug($groupName) }}"
                                        @if ($checked) checked @endif>
                                @endif
                                <label class="form-check-label mt-1" for="selectPermission{{ $permission->id }}">
                                    {{ $permission->name }}
                                </label>
                            </div>
                        </div>
                    @endforeach
                @else
                    @foreach ($permissions as $permission)
                        <div class="col permission-item" data-permission-name="{{ strtolower($permission->name) }}">
                            <div class="form-check ms-3">
                                <input class="form-check-input" type="checkbox" name="permission_id[]"
                                    value="{{ $permission->id }}"
                                    id="selectPermission{{ $permission->id }}"
                                    data-group-id="{{ Str::slug($groupName) }}"
                                    @if (in_array($permission->id, $selectedPermissionIds)) checked @endif>
                                <label class="form-check-label mt-1" for="selectPermission{{ $permission->id }}">
                                    {{ $permission->name }}
                                </label>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
        <hr>
    </div>
@endforeach
