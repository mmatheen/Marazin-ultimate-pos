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
                                        <th>Invoice Prefix</th>
                                        <th @cannot('create sublocation') style="display:none" @endcannot>Parent Location</th>
                                        <th @cannot('create sublocation') style="display:none" @endcannot>Vehicle Number</th>
                                        <th @cannot('create sublocation') style="display:none" @endcannot>Vehicle Type</th>
                                        <th>Address</th>
                                        <th>Province</th>
                                        <th>District</th>
                                        <th>City</th>
                                        <th>Email</th>
                                        <th>Mobile</th>
                                        <th>Invoice Layout</th>
                                        <th>Footer Note</th>
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
                                <input type="hidden" name="edit_id" id="edit_id">

                                {{-- ── Row 1: Name + Parent ── --}}
                                <div class="row g-3 mb-2">
                                    <div class="col-md-{{ Auth::user()->can('create sublocation') ? '6' : '12' }}">
                                        <div class="form-group local-forms mb-0">
                                            <label>Name <span class="login-danger">*</span></label>
                                            <input class="form-control" id="edit_name" name="name" type="text" placeholder="Location name">
                                            <span class="text-danger" id="name_error"></span>
                                        </div>
                                    </div>
                                    @can('create sublocation')
                                    <div class="col-md-6">
                                        <div class="form-group local-forms mb-0">
                                            <label>Parent Location <span class="text-muted">(Optional)</span></label>
                                            <select name="parent_id" id="edit_parent_id" class="form-control form-select">
                                                <option value="">No Parent (Main)</option>
                                            </select>
                                            <span class="text-danger" id="parent_id_error"></span>
                                        </div>
                                    </div>
                                    @endcan
                                </div>

                                {{-- ── Parent Info Banner (sublocation only) ── --}}
                                @can('create sublocation')
                                <div id="parentLocationDetails" style="display:none;" class="mb-2">
                                    <div class="alert alert-success border-0 py-2 mb-0"
                                        style="background:linear-gradient(135deg,#e8f5e8 0%,#d4edda 100%);border-radius:8px;">
                                        <div class="d-flex align-items-center mb-1">
                                            <i class="fas fa-building text-success me-2"></i>
                                            <strong class="text-success" id="parentLocationName">Parent Location Details</strong>
                                        </div>
                                        <div class="row g-1">
                                            <div class="col-6 col-md-3"><small class="text-muted">📍 Address</small><div id="parentAddress" class="fw-semibold small">-</div></div>
                                            <div class="col-6 col-md-3"><small class="text-muted">🏙️ City</small><div id="parentCity" class="fw-semibold small">-</div></div>
                                            <div class="col-6 col-md-3"><small class="text-muted">📍 District</small><div id="parentDistrict" class="fw-semibold small">-</div></div>
                                            <div class="col-6 col-md-3"><small class="text-muted">📞 Phone</small><div id="parentTelephone" class="fw-semibold small">-</div></div>
                                        </div>
                                        <div id="existingSublocations" style="display:none;" class="mt-2">
                                            <hr class="my-1" style="border-color:#28a745;opacity:0.3;">
                                            <small class="text-info fw-bold"><i class="fas fa-sitemap me-1"></i>Existing Sublocations</small>
                                            <div id="sublocationsList" class="d-flex flex-wrap gap-1 mt-1"></div>
                                        </div>
                                    </div>
                                </div>

                                @endcan

                                {{-- ── Vehicle Section (sublocation only) ── --}}
                                <div id="vehicleDetailsSection" style="display:none;" class="mb-2">
                                    <div class="alert alert-info py-2 mb-2"
                                        style="background:linear-gradient(135deg,#e3f2fd 0%,#bbdefb 100%);border-left:4px solid #2196f3;">
                                        <i class="fas fa-truck text-primary me-2"></i>
                                        <strong class="text-primary">Vehicle Details Required</strong>
                                        <small class="text-muted ms-2">Sublocations must have vehicle information</small>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="form-group local-forms mb-0">
                                                <label>Vehicle Number <span class="login-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-car"></i></span>
                                                    <input class="form-control" id="edit_vehicle_number" name="vehicle_number"
                                                        type="text" placeholder="e.g., ABC-1234" maxlength="20">
                                                </div>
                                                <span class="text-danger" id="vehicle_number_error"></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group local-forms mb-0">
                                                <label>Vehicle Type <span class="login-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-cogs"></i></span>
                                                    <select class="form-control form-select" id="edit_vehicle_type" name="vehicle_type">
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

                                {{-- ── Contact & Address Section (main location only) ── --}}
                                <div id="contactDetailsSection">
                                    <div class="row g-3 mb-2">
                                        <div class="col-md-4">
                                            <div class="form-group local-forms mb-0">
                                                <label>Address <span class="login-danger" id="address_required">*</span></label>
                                                <textarea class="form-control" id="edit_address" name="address"
                                                    rows="3" placeholder="Street address"></textarea>
                                                <span class="text-danger" id="address_error"></span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group local-forms mb-0">
                                                <label>Province <span class="login-danger" id="province_required">*</span></label>
                                                <select class="form-control form-select" id="edit_province" name="province">
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
                                        <div class="col-md-4">
                                            <div class="form-group local-forms mb-0">
                                                <label>District <span class="login-danger" id="district_required">*</span></label>
                                                <select class="form-control form-select" id="edit_district" name="district">
                                                    <option value="">Select District</option>
                                                </select>
                                                <span class="text-danger" id="district_error"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row g-3 mb-2">
                                        <div class="col-md-4">
                                            <div class="form-group local-forms mb-0">
                                                <label>City <span class="login-danger" id="city_required"></span></label>
                                                <input class="form-control" id="edit_city" name="city" type="text" placeholder="City">
                                                <span class="text-danger" id="city_error"></span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group local-forms mb-0">
                                                <label>Email <span class="login-danger" id="email_required"></span></label>
                                                <input type="text" class="form-control" id="edit_email" name="email" placeholder="Email address">
                                                <span class="text-danger" id="email_error"></span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group local-forms mb-0">
                                                <label>Phone <span class="login-danger" id="mobile_required"></span></label>
                                                <input type="text" class="form-control" id="edit_mobile" name="mobile" placeholder="Mobile number">
                                                <span class="text-danger" id="mobile_error"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row g-3 mb-2">
                                        <div class="col-md-4">
                                            <div class="form-group local-forms mb-0">
                                                <label>Telephone <span class="text-muted">(Optional)</span></label>
                                                <input type="text" class="form-control" id="edit_telephone_no" name="telephone_no" placeholder="Telephone number">
                                                <span class="text-danger" id="telephone_no_error"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- ── Logo · Prefix · Layout row ── --}}
                                <div id="logoAndLayoutSection">
                                    <hr class="my-3">
                                    <div class="row g-3 mb-2">
                                        <div class="col-md-4">
                                            <div class="form-group local-forms mb-0">
                                                <label>Location Logo</label>
                                                <input type="file" class="form-control" id="edit_logo_image"
                                                    name="logo_image" accept="image/*">
                                                <span class="text-danger" id="logo_image_error"></span>
                                                <div id="logo_preview" class="mt-2"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group local-forms mb-0">
                                                <label>Invoice Prefix <span class="login-danger">*</span></label>
                                                <input type="text" class="form-control" id="edit_invoice_prefix"
                                                    name="invoice_prefix" maxlength="10"
                                                    placeholder="e.g. ARM, AFS"
                                                    style="text-transform:uppercase; letter-spacing:2px; font-weight:600;">
                                                <span class="text-danger" id="invoice_prefix_error"></span>
                                                <small class="text-muted">Used in invoice numbers e.g. ARM → ARM-001</small>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group local-forms mb-0">
                                                <label>Receipt Layout <span class="login-danger">*</span></label>
                                                <select class="form-control form-select" id="edit_invoice_layout_pos"
                                                    name="invoice_layout_pos" required>
                                                    <option value="">Select Layout</option>
                                                    <option value="80mm">80mm Thermal Printer</option>
                                                    <option value="a4">A4 Size Printer</option>
                                                    <option value="dot_matrix">Dot Matrix (Half 5.5in)</option>
                                                    <option value="dot_matrix_full">Dot Matrix (Full 11in)</option>
                                                </select>
                                                <span class="text-danger" id="invoice_layout_pos_error"></span>
                                                <small class="text-muted">
                                                    <strong>80mm:</strong> Thermal |
                                                    <strong>A4:</strong> Detailed |
                                                    <strong>Dot Half:</strong> 8×5.5in |
                                                    <strong>Dot Full:</strong> 8×11in
                                                </small>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- ── Footer Note ── --}}
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="form-group local-forms mb-0">
                                                <label>Receipt Footer Note <span class="text-muted">(Optional)</span></label>
                                                <textarea class="form-control" id="edit_footer_note" name="footer_note"
                                                    rows="2" placeholder="e.g., Come again! Thank you for your business!"></textarea>
                                                <span class="text-danger" id="footer_note_error"></span>
                                                <small class="text-muted">Appears at the bottom of receipts. Leave blank for default.</small>
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

        {{-- Receipt Settings Modal --}}
        <div id="receiptSettingsModal" class="modal fade" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-receipt me-2"></i>Receipt Settings - 80mm Thermal Printer</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            {{-- Left Column: Settings Form --}}
                            <div class="col-md-5">
                                <form id="receiptSettingsForm">
                                    {{-- Preset Templates --}}
                                    <div class="card mb-3">
                                        <div class="card-header bg-primary text-white">
                                            <h6 class="mb-0"><i class="fas fa-magic me-2"></i>Quick Presets</h6>
                                        </div>
                                        <div class="card-body">
                                            <select id="receipt_preset" class="form-select">
                                                <option value="">-- Select Preset --</option>
                                            </select>
                                            <small class="text-muted">Apply pre-configured templates instantly</small>
                                        </div>
                                    </div>

                                    {{-- Display Options --}}
                                    <div class="card mb-3">
                                        <div class="card-header bg-info text-white">
                                            <h6 class="mb-0"><i class="fas fa-eye me-2"></i>Display Options</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="receipt_show_logo">
                                                <label class="form-check-label" for="receipt_show_logo">Show Logo</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="receipt_show_customer_phone">
                                                <label class="form-check-label" for="receipt_show_customer_phone">Show Customer Phone</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="receipt_show_mrp_strikethrough">
                                                <label class="form-check-label" for="receipt_show_mrp_strikethrough">Show MRP Strikethrough</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="receipt_show_imei">
                                                <label class="form-check-label" for="receipt_show_imei">Show IMEI Numbers</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="receipt_show_discount_breakdown">
                                                <label class="form-check-label" for="receipt_show_discount_breakdown">Show Discount Details</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="receipt_show_payment_method">
                                                <label class="form-check-label" for="receipt_show_payment_method">Show Payment Method</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="receipt_show_outstanding_due">
                                                <label class="form-check-label" for="receipt_show_outstanding_due">Show Outstanding Due</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="receipt_show_stats_section">
                                                <label class="form-check-label" for="receipt_show_stats_section">Show Stats (Items/Qty/Discount)</label>
                                            </div>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="receipt_show_footer_note">
                                                <label class="form-check-label" for="receipt_show_footer_note">Show Footer Note</label>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Layout Settings --}}
                                    <div class="card mb-3">
                                        <div class="card-header bg-success text-white">
                                            <h6 class="mb-0"><i class="fas fa-sliders-h me-2"></i>Layout Settings</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Spacing Mode</label>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="spacing_mode" value="compact" id="spacing_compact" checked>
                                                    <label class="form-check-label" for="spacing_compact">
                                                        <strong>Compact</strong> - Shrunk, fast print, less paper
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="spacing_mode" value="spacious" id="spacing_spacious">
                                                    <label class="form-check-label" for="spacing_spacious">
                                                        <strong>Spacious</strong> - Comfortable reading, more paper
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="receipt_font_size_base" class="form-label fw-bold">Font Size (9-14px)</label>
                                                <input type="number" class="form-control" id="receipt_font_size_base" min="9" max="14" value="11">
                                                <small class="text-muted">Base font size for receipt text</small>
                                            </div>

                                            <div class="mb-3">
                                                <label for="receipt_font_family" class="form-label fw-bold">Font Family</label>
                                                <select class="form-select" id="receipt_font_family">
                                                    <option value="Arial">Arial (Default)</option>
                                                    <option value="Courier New">Courier New (Monospace)</option>
                                                    <option value="Times New Roman">Times New Roman (Serif)</option>
                                                    <option value="Verdana">Verdana (Wide)</option>
                                                    <option value="Tahoma">Tahoma (Compact)</option>
                                                </select>
                                                <small class="text-muted">Font style for dot matrix &amp; thermal receipts</small>
                                            </div>

                                            <div class="mb-3">
                                                <label for="receipt_line_spacing" class="form-label fw-bold">
                                                    Line Spacing: <span id="line_spacing_value" class="badge bg-primary">5</span>
                                                </label>
                                                <input type="range" class="form-range" id="receipt_line_spacing" min="1" max="10" value="5">
                                                <div class="d-flex justify-content-between">
                                                    <small class="text-muted">Tight (1)</small>
                                                    <small class="text-muted">Relaxed (10)</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            {{-- Right Column: Live Preview --}}
                            <div class="col-md-7">
                                <div class="card h-100">
                                    <div class="card-header bg-secondary text-white">
                                        <h6 class="mb-0"><i class="fas fa-eye me-2"></i>Live Preview</h6>
                                    </div>
                                    <div class="card-body p-0" style="background: #f8f9fa;">
                                        <div class="text-center p-3" style="background: #fff; border-bottom: 1px solid #dee2e6;">
                                            <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Changes update automatically</small>
                                        </div>
                                        <div class="d-flex justify-content-center align-items-start p-3" style="min-height: 600px; overflow-y: auto;">
                                            <div style="width: 80mm; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                                <iframe id="receiptPreviewFrame" style="width: 100%; border: none; min-height: 800px;" frameborder="0"></iframe>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" id="resetReceiptSettings">
                            <i class="fas fa-undo me-1"></i> Reset to Defaults
                        </button>
                        <button type="button" class="btn btn-success" id="saveReceiptSettings">
                            <i class="fas fa-save me-1"></i> Save Settings
                        </button>
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('location.location_ajax')
@endsection

@push('scripts')
    {{-- Include receipt settings JS - Vite automatically handles versioning --}}
    @vite('resources/js/receipt-settings.js')
@endpush

