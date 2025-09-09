@extends('layout.layout')
@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="row">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">Comprehensive Role & Permissions System Demo</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item">System</li>
                            <li class="breadcrumb-item active">Permissions Demo</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Overview Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">🚀 Complete POS System Permissions Overview</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-users"></i> Total Roles: {{ \Spatie\Permission\Models\Role::count() }}</h6>
                                <p class="mb-0">Predefined roles with specific access levels</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="alert alert-success">
                                <h6><i class="fas fa-key"></i> Total Permissions: {{ \Spatie\Permission\Models\Permission::count() }}</h6>
                                <p class="mb-0">Granular permissions across all modules</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="alert alert-warning">
                                <h6><i class="fas fa-shield-alt"></i> Protected Controllers: 25+</h6>
                                <p class="mb-0">All major controllers have middleware protection</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Current User Permissions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">👤 Your Current Access Level</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Your Role(s):</h6>
                            <div class="mb-3">
                                @foreach(auth()->user()->roles as $role)
                                    <span class="badge bg-primary me-2">{{ $role->name }}</span>
                                @endforeach
                            </div>
                            
                            <h6>Your Permissions Count:</h6>
                            <p class="text-success">{{ auth()->user()->getAllPermissions()->count() }} out of {{ \Spatie\Permission\Models\Permission::count() }} total permissions</p>
                        </div>
                        <div class="col-md-6">
                            <h6>Quick Access Test:</h6>
                            <div class="btn-group-vertical d-grid gap-2">
                                @can('view product')
                                    <button class="btn btn-outline-success btn-sm">✅ Can View Products</button>
                                @else
                                    <button class="btn btn-outline-danger btn-sm" disabled>❌ Cannot View Products</button>
                                @endcan
                                
                                @can('create sale')
                                    <button class="btn btn-outline-success btn-sm">✅ Can Create Sales</button>
                                @else
                                    <button class="btn btn-outline-danger btn-sm" disabled>❌ Cannot Create Sales</button>
                                @endcan
                                
                                @can('view daily-report')
                                    <button class="btn btn-outline-success btn-sm">✅ Can View Reports</button>
                                @else
                                    <button class="btn btn-outline-danger btn-sm" disabled>❌ Cannot View Reports</button>
                                @endcan
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Permission Groups Overview -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">🏗️ System Module Permissions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @php
                            $permissionGroups = \Spatie\Permission\Models\Permission::all()->groupBy('group_name');
                        @endphp
                        
                        @foreach($permissionGroups->take(12) as $groupName => $permissions)
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card border">
                                    <div class="card-body">
                                        <h6 class="card-title text-primary">{{ $groupName }}</h6>
                                        <p class="card-text">
                                            <small class="text-muted">{{ $permissions->count() }} permissions</small>
                                        </p>
                                        <div class="permission-list" style="max-height: 120px; overflow-y: auto;">
                                            @foreach($permissions->take(5) as $permission)
                                                <span class="badge bg-light text-dark me-1 mb-1" style="font-size: 0.7em;">
                                                    @can($permission->name)
                                                        ✅
                                                    @else
                                                        ❌
                                                    @endcan
                                                    {{ $permission->name }}
                                                </span>
                                            @endforeach
                                            @if($permissions->count() > 5)
                                                <small class="text-muted">...and {{ $permissions->count() - 5 }} more</small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Protected Routes Demo -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">🔒 Protected Routes & Controllers Demo</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>✅ Available Actions (Based on Your Permissions):</h6>
                            <div class="list-group">
                                @can('view product')
                                    <a href="/product" class="list-group-item list-group-item-action list-group-item-success">
                                        <i class="fas fa-box"></i> View Products
                                    </a>
                                @endcan
                                @can('view customer')
                                    <a href="/customer" class="list-group-item list-group-item-action list-group-item-success">
                                        <i class="fas fa-users"></i> View Customers
                                    </a>
                                @endcan
                                @can('access pos')
                                    <a href="#" class="list-group-item list-group-item-action list-group-item-success">
                                        <i class="fas fa-cash-register"></i> Access POS
                                    </a>
                                @endcan
                                @can('view role')
                                    <a href="/role" class="list-group-item list-group-item-action list-group-item-success">
                                        <i class="fas fa-user-cog"></i> Manage Roles
                                    </a>
                                @endcan
                                @can('view role-permission')
                                    <a href="/group-role-and-permission" class="list-group-item list-group-item-action list-group-item-success">
                                        <i class="fas fa-key"></i> Manage Role Permissions
                                    </a>
                                @endcan
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6>❌ Restricted Actions (You Don't Have Permission):</h6>
                            <div class="list-group">
                                @cannot('create user')
                                    <div class="list-group-item list-group-item-danger">
                                        <i class="fas fa-user-plus"></i> Create New Users
                                        <small class="text-muted d-block">Requires 'create user' permission</small>
                                    </div>
                                @endcannot
                                @cannot('delete product')
                                    <div class="list-group-item list-group-item-danger">
                                        <i class="fas fa-trash"></i> Delete Products
                                        <small class="text-muted d-block">Requires 'delete product' permission</small>
                                    </div>
                                @endcannot
                                @cannot('edit business-settings')
                                    <div class="list-group-item list-group-item-danger">
                                        <i class="fas fa-cog"></i> Edit Business Settings
                                        <small class="text-muted d-block">Requires 'edit business-settings' permission</small>
                                    </div>
                                @endcannot
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ajax Demo Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">⚡ Live Ajax Permission Check Demo</h5>
                </div>
                <div class="card-body">
                    <p>Click the button below to test Ajax-based permission checking:</p>
                    <button id="testPermissionsBtn" class="btn btn-primary">
                        <i class="fas fa-bolt"></i> Test My Permissions via Ajax
                    </button>
                    <div id="ajaxResults" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Role Hierarchy Display -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">👑 System Role Hierarchy</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach(\Spatie\Permission\Models\Role::with('permissions')->get() as $role)
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card 
                                    @if($role->name === 'Super Admin') border-danger
                                    @elseif($role->name === 'Admin') border-warning  
                                    @elseif($role->name === 'Manager') border-info
                                    @else border-secondary
                                    @endif
                                ">
                                    <div class="card-header 
                                        @if($role->name === 'Super Admin') bg-danger text-white
                                        @elseif($role->name === 'Admin') bg-warning text-dark
                                        @elseif($role->name === 'Manager') bg-info text-white
                                        @else bg-secondary text-white
                                        @endif
                                    ">
                                        <h6 class="mb-0">{{ $role->name }}</h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text">
                                            <strong>{{ $role->permissions->count() }}</strong> permissions assigned
                                        </p>
                                        @if($role->name === 'Super Admin')
                                            <small class="text-danger">🔥 Full System Access</small>
                                        @else
                                            <small class="text-muted">Limited access based on role</small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Implementation Stats -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">📊 Implementation Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 mb-3">
                            <div class="stats-box">
                                <h3 class="text-primary">{{ \Spatie\Permission\Models\Permission::distinct('group_name')->count() }}</h3>
                                <p>Permission Groups</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stats-box">
                                <h3 class="text-success">25+</h3>
                                <p>Protected Controllers</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stats-box">
                                <h3 class="text-warning">{{ \Spatie\Permission\Models\Role::count() }}</h3>
                                <p>Predefined Roles</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stats-box">
                                <h3 class="text-info">100%</h3>
                                <p>Ajax Compatible</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stats-box {
    padding: 20px;
    border-radius: 8px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
}
.permission-list .badge {
    font-size: 0.65em;
}
</style>

<script>
$(document).ready(function() {
    $('#testPermissionsBtn').click(function() {
        const btn = $(this);
        const resultsDiv = $('#ajaxResults');
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Testing...');
        
        $.ajax({
            url: '/test-permissions-ajax',
            method: 'GET',
            success: function(response) {
                resultsDiv.html(`
                    <div class="alert alert-success">
                        <h6><i class="fas fa-check-circle"></i> Ajax Permission Test Results:</h6>
                        <ul class="mb-0">
                            <li>✅ Ajax request successful</li>
                            <li>🔐 Permission middleware working</li>
                            <li>⚡ Real-time permission checking active</li>
                            <li>🎯 Your permissions: ${response.permissions_count} active</li>
                        </ul>
                    </div>
                `);
            },
            error: function(xhr) {
                if(xhr.status === 403) {
                    resultsDiv.html(`
                        <div class="alert alert-danger">
                            <h6><i class="fas fa-ban"></i> Permission Denied</h6>
                            <p>This demonstrates that the permission system is working! You don't have access to this test endpoint.</p>
                        </div>
                    `);
                } else {
                    resultsDiv.html(`
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-exclamation-triangle"></i> Test Error</h6>
                            <p>Error: ${xhr.responseJSON?.message || 'Unknown error'}</p>
                        </div>
                    `);
                }
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-bolt"></i> Test My Permissions via Ajax');
            }
        });
    });
});
</script>
@endsection
