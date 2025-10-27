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

            #viewPermissionsModal .modal-body {
                padding: 30px;
                background-color: #f8f9fa;
                max-height: 70vh;
                overflow-y: auto;
            }

            /* Permissions Grid Layout */
            .permissions-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
                gap: 20px;
            }

            /* Permission Card */
            .permission-card {
                background: white;
                border-radius: 4px;
                overflow: hidden;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                transition: all 0.2s ease;
                border: 1px solid #dee2e6;
                border-left: 3px solid;
            }

            .permission-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            }

            /* Color variations for different cards - Using your app colors */
            .permission-card.color-1 { border-left-color: #1372ee; }
            .permission-card.color-2 { border-left-color: #26C76F; }
            .permission-card.color-3 { border-left-color: #e74c3c; }
            .permission-card.color-4 { border-left-color: #f39c12; }
            .permission-card.color-5 { border-left-color: #9b59b6; }
            .permission-card.color-6 { border-left-color: #1abc9c; }

            /* Permission Card Header */
            .permission-card-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 16px 20px;
                background-color: #f8f9fa;
                border-bottom: 1px solid #dee2e6;
            }

            .permission-card-header .header-left {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .permission-card-header i {
                font-size: 20px;
                color: #495057;
            }

            .permission-card-header .group-title {
                font-weight: 600;
                font-size: 15px;
                color: #333;
            }

            .permission-card-header .badge-count {
                background-color: #1372ee;
                color: white;
                padding: 4px 12px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: 600;
                min-width: 30px;
                text-align: center;
            }

            /* Permission Card Body */
            .permission-card-body {
                padding: 16px;
                display: flex;
                flex-direction: column;
                gap: 8px;
                max-height: 300px;
                overflow-y: auto;
            }

            /* Custom scrollbar */
            .permission-card-body::-webkit-scrollbar {
                width: 6px;
            }

            .permission-card-body::-webkit-scrollbar-track {
                background: #f1f1f1;
                border-radius: 10px;
            }

            .permission-card-body::-webkit-scrollbar-thumb {
                background: #ccc;
                border-radius: 10px;
            }

            .permission-card-body::-webkit-scrollbar-thumb:hover {
                background: #999;
            }

            /* Permission Pill */
            .permission-pill {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 8px 12px;
                background-color: #fff;
                border: 1px solid #e9ecef;
                border-radius: 6px;
                font-size: 13px;
                color: #495057;
                transition: all 0.2s ease;
                cursor: default;
            }

            .permission-pill:hover {
                background-color: #f8f9fa;
                border-color: #dee2e6;
                transform: translateX(4px);
            }

            .permission-pill i {
                font-size: 14px;
                color: #1372ee;
                flex-shrink: 0;
            }

            .permission-pill span {
                font-weight: 500;
                line-height: 1.4;
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
                .permissions-grid {
                    grid-template-columns: 1fr;
                }
                
                #viewPermissionsModal .modal-dialog {
                    max-width: 95%;
                    margin: 10px auto;
                }

                .permission-card-body {
                    max-height: 200px;
                }
            }

            /* Loading animation for modal */
            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .permission-card {
                animation: fadeIn 0.3s ease forwards;
            }

            .permission-card:nth-child(1) { animation-delay: 0.05s; }
            .permission-card:nth-child(2) { animation-delay: 0.1s; }
            .permission-card:nth-child(3) { animation-delay: 0.15s; }
            .permission-card:nth-child(4) { animation-delay: 0.2s; }
            .permission-card:nth-child(5) { animation-delay: 0.25s; }
            .permission-card:nth-child(6) { animation-delay: 0.3s; }
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
                        <h5 class="modal-title" id="permissionsModalLabel">
                            <i class="feather-shield"></i> Permissions List
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="permissionsModalBody">
                        <!-- Permissions will be loaded here dynamically -->
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
