@extends('layout.layout')

@section('styles')
    <style>
        #vehicleDetailsSection,
        #parentLocationDetails {
            animation: slideIn 0.3s ease-in-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .vehicle-alert {
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(33, 150, 243, 0.1);
        }

        .input-group-text {
            border-right: none;
            background: #f8f9fa;
        }

        .input-group .form-control,
        .input-group .form-select {
            border-left: none;
        }

        .input-group:focus-within .input-group-text {
            border-color: #80bdff;
            background: #e7f3ff;
        }

        .input-group:focus-within .form-control,
        .input-group:focus-within .form-select {
            border-color: #80bdff;
        }

        /* Compact sublocation styling */
        .sublocation-badge {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 8px 12px;
            transition: all 0.2s ease;
            display: inline-block;
            margin: 2px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .sublocation-badge:hover {
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
            transform: translateY(-1px);
        }

        .sublocation-vehicle {
            background: #e3f2fd;
            color: #1976d2;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 0.75em;
            font-weight: 500;
            margin-left: 5px;
        }

        .parent-info-row {
            margin-bottom: 0 !important;
        }

        .parent-info-row .col-md-3 {
            padding: 4px 8px;
        }

        /* Compact form spacing */
        .modal-xl {
            max-width: 95%;
        }

        .form-group.local-forms {
            margin-bottom: 0.8rem;
        }
    </style>
@endsection

@section('content')
    <div class="content container-fluid">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Location</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="#">Business Settings</a></li>
                                <li class="breadcrumb-item active">Locations</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- table row --}}
        <div class="row">
            <div class="col-sm-12">
                <div class="card card-table">
                    <div class="card-body">
                        <div class="page-header">
                            <div class="row align-items-center">
                                <div class="col-auto text-end float-end ms-auto download-grp">
                                    <!-- Button trigger modal -->

                                    @can('create location')
                                        <button type="button" class="btn btn-outline-info " id="addLocationButton">
                                            New <i class="fas fa-plus px-2"> </i>
                                        </button>
                                    @endcan
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="location" class="datatable table table-stripped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Location ID</th>
                                        <th>Parent Location</th>
                                        <th>Vehicle Number</th>
                                        <th>Vehicle Type</th>
                                        <th>Address</th>
                                        <th>Province</th>
                                        <th>District</th>
                                        <th>City</th>
                                        <th>Email</th>
                                        <th>Mobile</th>
                                        <th>Invoice Layout</th>
                                        <th>Logo</th>
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

        {{-- Add/Edit modal row --}}
        <div class="row">
            <div id="addAndEditLocationModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
                    <div class="modal-content">
                        <div class="modal-body">
                            <div class="text-center mt-2 mb-4">
                                <h5 id="modalTitle"></h5>
                            </div>
                            <form id="addAndLocationUpdateForm" enctype="multipart/form-data">
                                <div class="row">
                                    <input type="hidden" name="edit_id" id="edit_id">

                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Name<span class="login-danger">*</span></label>
                                                <input class="form-control" id="edit_name" name="name" type="text"
                                                    placeholder="Name">
                                                <span class="text-danger" id="name_error"></span>
                                            </div>
                                        </div>
                                    </div>
                                    {{-- <div class="col-md-4">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Location ID<span class="login-danger">*</span></label>
                                                <input class="form-control" id="edit_location_id" name="location_id"
                                                    type="text" placeholder="location ID">
                                                <span class="text-danger" id="location_id_error"></span>
                                            </div>
                                        </div>
                                    </div> --}}
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Parent Location (Optional)</label>
                                                <select name="parent_id" id="edit_parent_id"
                                                    class="form-control form-select">
                                                    <option value="">No Parent (Main)</option>
                                                </select>
                                                <span class="text-danger" id="parent_id_error"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Parent Location Details Section (Initially Hidden) -->
                                    <div id="parentLocationDetails" class="col-12" style="display: none;">
                                        <div class="alert alert-success border-0 mb-3"
                                            style="background: linear-gradient(135deg, #e8f5e8 0%, #d4edda 100%); border-radius: 10px;">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-building text-success me-2"></i>
                                                <h6 class="mb-0 text-success fw-bold" id="parentLocationName">Parent
                                                    Location Details</h6>
                                            </div>

                                            <div class="row g-2 mb-2">
                                                <div class="col-md-3">
                                                    <small class="text-muted d-block">📍 Address</small>
                                                    <span id="parentAddress" class="fw-semibold">-</span>
                                                </div>
                                                <div class="col-md-3">
                                                    <small class="text-muted d-block">🏙️ City</small>
                                                    <span id="parentCity" class="fw-semibold">-</span>
                                                </div>
                                                <div class="col-md-3">
                                                    <small class="text-muted d-block">📍 District</small>
                                                    <span id="parentDistrict" class="fw-semibold">-</span>
                                                </div>
                                                <div class="col-md-3">
                                                    <small class="text-muted d-block">📞 Phone</small>
                                                    <span id="parentTelephone" class="fw-semibold">-</span>
                                                </div>
                                            </div>

                                            <!-- Existing Sublocations -->
                                            <div id="existingSublocations" style="display: none;">
                                                <hr class="my-2" style="border-color: #28a745; opacity: 0.3;">
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="fas fa-sitemap text-info me-2"></i>
                                                    <small class="text-info fw-bold">Existing Sublocations</small>
                                                </div>
                                                <div id="sublocationsList" class="d-flex flex-wrap gap-2">
                                                    <!-- Sublocations will be populated here -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Vehicle Details Section (Initially Hidden) -->
                                    <div id="vehicleDetailsSection" class="col-12" style="display: none;">
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="alert alert-info border-info vehicle-alert mb-3"
                                                    style="background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); border-left: 4px solid #2196f3;">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-truck text-primary me-2"
                                                            style="font-size: 1.2em;"></i>
                                                        <div>
                                                            <strong class="text-primary">Vehicle Details Required</strong>
                                                            <br>
                                                            <small class="text-muted">Sublocations must have vehicle
                                                                information for delivery tracking</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <div class="form-group local-forms">
                                                        <label class="form-label d-flex align-items-center">
                                                            <i class="fas fa-hashtag text-primary me-2"></i>
                                                            Vehicle Number <span class="login-danger">*</span>
                                                        </label>
                                                        <div class="input-group">
                                                            <span class="input-group-text"><i
                                                                    class="fas fa-car"></i></span>
                                                            <input class="form-control" id="edit_vehicle_number"
                                                                name="vehicle_number" type="text"
                                                                placeholder="e.g., ABC-1234" maxlength="20">
                                                        </div>
                                                        <span class="text-danger" id="vehicle_number_error"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <div class="form-group local-forms">
                                                        <label class="form-label d-flex align-items-center">
                                                            <i class="fas fa-truck text-primary me-2"></i>
                                                            Vehicle Type <span class="login-danger">*</span>
                                                        </label>
                                                        <div class="input-group">
                                                            <span class="input-group-text"><i
                                                                    class="fas fa-cogs"></i></span>
                                                            <select class="form-control form-select"
                                                                id="edit_vehicle_type" name="vehicle_type">
                                                                <option value="">Select Vehicle Type</option>
                                                                <option value="Van">🚐 Van</option>
                                                                <option value="Truck">🚛 Truck</option>
                                                                <option value="Bike">🏍️ Bike</option>
                                                                <option value="Car">🚗 Car</option>
                                                                <option value="Three Wheeler">🛺 Three Wheeler</option>
                                                                <option value="Lorry">🚚 Lorry</option>
                                                                <option value="Other">🚙 Other</option>
                                                            </select>
                                                        </div>
                                                        <span class="text-danger" id="vehicle_type_error"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Contact and Address Details Section (Hidden for Sublocations) -->
                                    <div id="contactDetailsSection" class="col-12">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <div class="form-group local-forms">
                                                        <label>Address<span class="login-danger"
                                                                id="address_required">*</span></label>
                                                        <textarea class="form-control" id="edit_address" name="address" placeholder="Address"></textarea>
                                                        <span class="text-danger" id="address_error"></span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <div class="form-group local-forms">
                                                        <label>Province<span class="login-danger"
                                                                id="province_required">*</span></label>
                                                        <select class="form-control form-select" id="edit_province"
                                                            name="province">
                                                            <option value="">Select Province</option>
                                                            <option value="Western">Western</option>
                                                            <option value="Central">Central</option>
                                                            <option value="Southern">Southern</option>
                                                            <option value="North Western">North Western</option>
                                                            <option value="North Central">North Central</option>
                                                            <option value="Northern">Northern</option>
                                                            <option value="Eastern">Eastern</option>
                                                            <option value="Uva">Uva</option>
                                                            <option value="Sabaragamuwa">Sabaragamuwa</option>
                                                        </select>
                                                        <span class="text-danger" id="province_error"></span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <div class="form-group local-forms">
                                                        <label>District<span class="login-danger"
                                                                id="district_required">*</span></label>
                                                        <select class="form-control form-select" id="edit_district"
                                                            name="district">
                                                            <option value="">Select District</option>
                                                        </select>
                                                        <span class="text-danger" id="district_error"></span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <div class="form-group local-forms">
                                                        <label>City<span class="login-danger"
                                                                id="city_required"></span></label>
                                                        <input class="form-control" id="edit_city" name="city"
                                                            type="text" placeholder="City">
                                                        <span class="text-danger" id="city_error"></span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <div class="form-group local-forms">
                                                        <label>Email<span class="login-danger"
                                                                id="email_required"></span></label>
                                                        <input type="text" class="form-control" id="edit_email"
                                                            name="email" placeholder="Email">
                                                        <span class="text-danger" id="email_error"></span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <div class="form-group local-forms">
                                                        <label>Phone<span class="login-danger"
                                                                id="mobile_required"></span></label>
                                                        <input type="text" class="form-control" id="edit_mobile"
                                                            name="mobile" placeholder="Phone No">
                                                        <span class="text-danger" id="mobile_error"></span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <div class="form-group local-forms">
                                                        <label>Telephone (Optional)</label>
                                                        <input type="text" class="form-control" id="edit_telephone_no"
                                                            name="telephone_no" placeholder="Telephone No">
                                                        <span class="text-danger" id="telephone_no_error"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Logo and Invoice Layout Section (Always Visible) -->
                                    <div id="logoAndLayoutSection" class="col-12">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <div class="form-group local-forms">
                                                        <label>Location Logo<span class="login-danger"></span></label>
                                                        <input type="file" class="form-control" id="edit_logo_image"
                                                            name="logo_image" accept="image/*">
                                                        <span class="text-danger" id="logo_image_error"></span>
                                                        <div id="logo_preview" style="margin-top: 10px;">
                                                            <!-- Logo preview will be shown here -->
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Invoice Layout Selection -->
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <div class="form-group local-forms">
                                                        <label>Receipt Layout for POS <span
                                                                class="login-danger">*</span></label>
                                                        <select class="form-control form-select"
                                                            id="edit_invoice_layout_pos" name="invoice_layout_pos"
                                                            required>
                                                            <option value="">Select Receipt Layout</option>
                                                            <option value="80mm">80mm Thermal Printer</option>
                                                            <option value="a4">A4 Size Printer</option>
                                                            <option value="dot_matrix">Dot Matrix Printer</option>
                                                        </select>
                                                        <span class="text-danger" id="invoice_layout_pos_error"></span>
                                                        <small class="text-muted mt-1">
                                                            <strong>80mm:</strong> Standard thermal receipt |
                                                            <strong>A4:</strong> Detailed invoice |
                                                            <strong>Dot Matrix:</strong> Traditional format
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="submit" id="modalButton" class="btn btn-outline-primary">Save</button>
                                    <button type="button" class="btn btn-outline-danger"
                                        data-bs-dismiss="modal">Close</button>
                                </div>
                            </form>
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
                                        <button type="submit"
                                            class="confirm_delete_btn btn btn-primary paid-continue-btn"
                                            style="width: 100%;">Delete</button>
                                    </div>
                                    <div class="col-6">
                                        <a data-bs-dismiss="modal" class="btn btn-primary paid-cancel-btn">Cancel
                                        </a>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('location.location_ajax')
@endsection
