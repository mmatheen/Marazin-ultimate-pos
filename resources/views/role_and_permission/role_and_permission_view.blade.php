@extends('layout.layout')
@section('content')
    <div class="content container-fluid">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Role & Permissions</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="#">User Management</a></li>
                                <li class="breadcrumb-item active">Role & Permissions</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            #roleAndPermission td:nth-child(3) {
                max-width: 300px;
                padding: 15px;
            }

            /* Permission count badge styling */
            .permission-count-badge {
                display: inline-flex;
                align-items: center;
                padding: 6px 14px;
                font-size: 13px;
                font-weight: 600;
                color: #495057;
                background-color: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 6px;
            }

            /* View All button styling */
            .view-permissions-btn {
                font-size: 12px;
                padding: 5px 12px;
                border-radius: 5px;
                transition: all 0.3s ease;
            }

            .view-permissions-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            }

            /* Modal styling */
            #viewPermissionsModal .modal-dialog {
                max-width: 1000px;
            }

            #viewPermissionsModal .modal-header {
                background-color: #fff;
                color: #333;
                border-bottom: 2px solid #e9ecef;
                padding: 20px 30px;
            }

            #viewPermissionsModal .modal-title {
                font-size: 22px;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 12px;
                color: #333;
            }

            #viewPermissionsModal .modal-title i {
                font-size: 24px;
                color: #1372ee;
            }

            #viewPermissionsModal .btn-close {
                opacity: 1;
            }

            #viewPermissionsModal .modal-body.permissions-modal-body {
                padding: 0;
                background-color: #fff;
                max-height: 70vh;
                display: flex;
                flex-direction: column;
                overflow: hidden;
            }

            .permissions-search-bar {
                padding: 14px 24px;
                border-bottom: 1px solid #e9ecef;
                background-color: #fff;
                flex-shrink: 0;
            }

            .permissions-search-bar .form-control {
                font-size: 14px;
                border-radius: 6px;
            }

            #permissionsListContainer {
                padding: 16px 24px 20px;
                overflow-y: auto;
                flex: 1;
            }

            .permissions-no-results {
                text-align: center;
                padding: 24px 0;
                font-size: 14px;
            }

            .permissions-view-simple {
                display: flex;
                flex-direction: column;
                gap: 16px;
            }

            .permission-group-block {
                border: 1px solid #e9ecef;
                border-radius: 6px;
                overflow: hidden;
            }

            .permission-group-title {
                margin: 0;
                padding: 10px 14px;
                font-size: 14px;
                font-weight: 600;
                color: #333;
                background-color: #f8f9fa;
                border-bottom: 1px solid #e9ecef;
            }

            .permission-group-title span {
                font-weight: 500;
                color: #6c757d;
                font-size: 13px;
            }

            .permission-card-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
                padding: 12px 14px;
                margin: 0;
                background-color: #fafbfc;
            }

            .permission-mini-card {
                background: #fff;
                border: 1px solid #e9ecef;
                border-left: 3px solid #1372ee;
                border-radius: 6px;
                padding: 8px 10px;
                font-size: 12px;
                color: #495057;
                line-height: 1.4;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
                transition: border-color 0.15s ease, box-shadow 0.15s ease;
            }

            .permission-mini-card:hover {
                border-color: #c5d9f5;
                box-shadow: 0 2px 6px rgba(19, 114, 238, 0.12);
            }

            .permission-mini-card span {
                display: block;
                word-break: break-word;
                font-weight: 500;
            }

            /* Modal footer */
            #viewPermissionsModal .modal-footer {
                border-top: 1px solid #e9ecef;
                padding: 15px 30px;
                background-color: #fff;
            }

            #viewPermissionsModal .modal-footer .btn {
                padding: 8px 24px;
                border-radius: 6px;
                font-weight: 600;
            }

            /* Improve table row spacing */
            #roleAndPermission tbody tr {
                border-bottom: 1px solid #f0f0f0;
            }

            #roleAndPermission tbody tr:hover {
                background-color: #f8f9fa;
            }

            /* Responsive adjustments */
            @media (max-width: 768px) {
                #viewPermissionsModal .modal-dialog {
                    max-width: 95%;
                    margin: 10px auto;
                }

                .permission-card-grid {
                    grid-template-columns: 1fr;
                }
            }

        </style>

        {{-- table row --}}
        <div class="row">
            <div class="col-sm-12">
                <div class="card card-table">
                    <div class="card-body">
                        <div class="page-header">
                            <div class="row align-items-center">
                                <div class="col-auto text-end float-end ms-auto download-grp">
                                    <!-- Button trigger modal -->
                                    @can('create role-permission')
                                        <a type="button" class="btn btn-outline-info "
                                            href="{{ route('group-role-and-permission') }}">+ New Role & Permissions</a>
                                    @endcan
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="roleAndPermission" class="datatable table table-stripped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Role Name</th>
                                        <th>Permissions Name</th>
                                        <th>Actions</th>
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

        {{-- Delete modal --}}
        <div id="deleteModal" class="modal custom-modal fade" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="form-header">
                            <h3 id="deleteName"></h3>
                            <p>Are you sure want to delete?</p>
                        </div>
                        <div class="modal-btn delete-action">
                            <div class="row">
                                <input type="hidden" id="deleting_id">
                                <div class="row">
                                    <div class="col-6">
                                        <button type="submit" class="confirm_delete_btn btn btn-primary paid-continue-btn"
                                            style="width: 100%;">Delete</button>
                                    </div>
                                    <div class="col-6">
                                        <a data-bs-dismiss="modal" class="btn btn-primary paid-cancel-btn">Cancel</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- View All Permissions Modal --}}
        <div class="modal fade" id="viewPermissionsModal" tabindex="-1" aria-labelledby="permissionsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="permissionsModalLabel">Permissions</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body permissions-modal-body">
                        <div class="permissions-search-bar">
                            <input type="text" class="form-control" id="permissionsSearchInput"
                                placeholder="Search by group or permission name..." autocomplete="off">
                        </div>
                        <div id="permissionsListContainer"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('role_and_permission.role_and_permission_ajax')
@endsection
