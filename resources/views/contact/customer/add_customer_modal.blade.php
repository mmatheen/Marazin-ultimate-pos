{{-- Add/Edit modal row --}}
<style>
    /* Custom styling for city dropdown with plus button */
    .city-select-container {
        position: relative;
    }

    .city-select-container .select2-container {
        width: 100% !important;
    }

    .city-select-container .select2-container .select2-selection--single {
        height: 38px;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
    }

    .city-select-container .select2-container .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
        padding-left: 12px;
        color: #495057;
    }

    .city-select-container .select2-container .select2-selection--single .select2-selection__arrow {
        height: 36px;
        right: 10px;
    }

    /* Ensure proper alignment */
    .d-flex.align-items-start.gap-2 {
        gap: 8px !important;
    }
</style>
<div class="row">
    <div id="addAndEditCustomerModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="text-center mt-2 mb-4">
                        <h5 id="modalTitle"></h5>
                    </div>
                    <form id="addAndUpdateForm">
                        <div class="row">
                            <input type="hidden" name="edit_id" id="edit_id">

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label>Prefix</label>
                                    <select class="form-control form-select selectBox" id="edit_prefix" name="prefix">
                                        <option selected disabled>Mr / Mrs / Miss</option>
                                        <option>Mr</option>
                                        <option>Mrs</option>
                                        <option>Ms</option>
                                        <option>Miss</option>
                                    </select>
                                    <span class="text-danger" id="prefix_error"></span>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label>First Name<span class="login-danger">*</span></label>
                                    <input class="form-control" id="edit_first_name" name="first_name" type="text"
                                        placeholder="First Name">
                                    <span class="text-danger" id="first_name_error"></span>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label>Last Name</label>
                                    <input class="form-control" id="edit_last_name" name="last_name" type="text"
                                        placeholder="Last Name">
                                    <span class="text-danger" id="last_name_error"></span>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label>Email</label>
                                    <input type="email" class="form-control" id="edit_email" name="email"
                                        placeholder="Email">
                                    <span class="text-danger" id="email_error"></span>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label>Mobile No<span class="login-danger">*</span></label>
                                    <input type="text" class="form-control" id="edit_mobile_no" name="mobile_no"
                                        placeholder="Mobile">
                                    <span class="text-danger" id="mobile_no_error"></span>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label>Opening Balance</label>
                                    <input type="text" class="form-control" id="edit_opening_balance"
                                        name="opening_balance" placeholder="Opening Balance">
                                    <span class="text-danger" id="opening_balance_error"></span>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label>City
                                        @if (auth()->user()->hasRole('Sales Rep'))
                                            <span class="login-danger">*</span>
                                        @else
                                            <small class="text-muted">(Optional)</small>
                                        @endif
                                    </label>
                                    <div class="d-flex align-items-start gap-2">
                                        <div class="flex-grow-1 city-select-container">
                                            <select class="form-control form-select selectBox" id="edit_city_id"
                                                name="city_id" style="width: 100%;">
                                                <option value="">Select City</option>
                                            </select>
                                        </div>
                                        <button type="button" class="btn btn-outline-info btn-sm mt-0"
                                            id="addCityButton" style="min-width: 40px; height: 38px;">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                    <span class="text-danger" id="city_id_error"></span>
                                    @if (!auth()->user()->hasRole('Sales Rep'))
                                        <small class="text-muted">City selection helps sales reps filter customers by
                                            location</small>
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label>Credit Limit</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="edit_credit_limit"
                                            name="credit_limit" placeholder="Credit Limit">
                                    </div>
                                    <span class="text-danger" id="credit_limit_error"></span>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label>Customer Type<span class="login-danger">*</span></label>
                                    <select class="form-control form-select selectBox" id="edit_customer_type"
                                        name="customer_type">
                                        <option value="">Select Customer Type</option>
                                        <option value="wholesaler">Wholesaler</option>
                                        <option value="retailer" selected>Retailer</option>
                                    </select>
                                    <span class="text-danger" id="customer_type_error"></span>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label>Address</label>
                                    <textarea class="form-control" id="edit_address" name="address" rows="2" placeholder="Address"></textarea>
                                    <span class="text-danger" id="address_error"></span>
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
