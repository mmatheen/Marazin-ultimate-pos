@extends('layout.layout')
@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm-12">
                <div class="page-sub-header">
                    <h3 class="page-title">Role Hierarchy Test</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Current User Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong> {{ auth()->user()->full_name }}</p>
                    <p><strong>Email:</strong> {{ auth()->user()->email }}</p>
                    <p><strong>Role:</strong> {{ auth()->user()->roles->first()->name ?? 'No Role' }}</p>
                    <p><strong>Is Master Super Admin:</strong> {{ auth()->user()->isMasterSuperAdmin() ? 'Yes' : 'No' }}</p>
                    <p><strong>Is Super Admin:</strong> {{ auth()->user()->isSuperAdmin() ? 'Yes' : 'No' }}</p>
                    <p><strong>Can Bypass Location Scope:</strong> {{ auth()->user()->canBypassLocationScope() ? 'Yes' : 'No' }}</p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Visible Roles (What you can see/assign)</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        @foreach(auth()->user()->getVisibleRoles() as $role)
                            <li class="list-group-item">
                                {{ $role->name }}
                                @if($role->is_system_role)
                                    <span class="badge bg-danger">System Role</span>
                                @endif
                                @if($role->is_master_admin)
                                    <span class="badge bg-warning">Master Admin</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Visible Users (Based on Role Hierarchy)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Locations</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(auth()->user()->getVisibleUsers()->with(['roles', 'locations'])->get() as $user)
                                    <tr>
                                        <td>{{ $user->full_name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>
                                            {{ $user->roles->first()->name ?? 'No Role' }}
                                            @if($user->roles->first() && $user->roles->first()->is_master_admin)
                                                <span class="badge bg-warning">Master</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($user->locations->count() > 0)
                                                {{ $user->locations->pluck('name')->join(', ') }}
                                            @else
                                                <span class="text-muted">All Locations</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
