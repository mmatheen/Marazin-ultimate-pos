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
        cursor: text;
        /* Make it look like a text input */
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    .city-select-container .select2-container .select2-selection--single:hover {
        border-color: #80bdff;
        /* Hover effect like text input */
    }

    .city-select-container .select2-container .select2-selection--single:focus-within {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        /* Focus effect like text input */
    }

    .city-select-container .select2-container .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
        padding-left: 12px;
        color: #495057;
        font-style: italic;
        /* Make placeholder text look different */
    }

    /* When value is selected, remove italic */
    .city-select-container .select2-container--default .select2-selection--single .select2-selection__rendered[title]:not([title=""]) {
        font-style: normal;
        color: #495057;
    }

    .city-select-container .select2-container .select2-selection--single .select2-selection__arrow {
        height: 36px;
        right: 10px;
        opacity: 0.5;
        /* Make arrow less prominent */
    }

    .city-select-container .select2-container .select2-selection--single:hover .select2-selection__arrow {
        opacity: 1;
        /* Show arrow more prominently on hover */
    }

    /* Style for Select2 search input */
    .city-select-container .select2-search--dropdown .select2-search__field {
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        padding: 8px 12px;
        font-size: 14px;
        width: 100% !important;
        background-color: #fff;
    }

    .city-select-container .select2-search--dropdown .select2-search__field:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        outline: none;
    }

    /* Dropdown container styling */
    .city-select-container .select2-dropdown {
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    /* Search results styling */
    .city-select-container .select2-results__option {
        padding: 8px 12px;
        cursor: pointer;
    }

    .city-select-container .select2-results__option--highlighted {
        background-color: #007bff;
        color: white;
    }

    /* Simple city search styling */
    .city-search-container {
        position: relative;
    }

    .city-search-input {
        width: 100%;
        height: 38px;
        padding: 8px 12px;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        transition: border-color 0.15s ease-in-out;
    }

    .city-search-input:focus,
    .city-search-input:hover {
        border-color: #80bdff;
        outline: none;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .city-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        z-index: 9999;
        background: white;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        max-height: 200px;
        overflow-y: auto;
    }

    .city-option {
        padding: 10px 12px;
        cursor: pointer;
        border-bottom: 1px solid #f8f9fa;
    }

    .city-option:hover,
    .city-option.highlighted {
        background-color: #007bff;
        color: white;
    }

    .city-option:last-child {
        border-bottom: none;
    }

    .city-no-results {
        padding: 10px 12px;
        color: #6c757d;
        text-align: center;
        font-style: italic;
    }

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
                                        <div class="flex-grow-1 city-search-container position-relative">
                                            <!-- Hidden input to store the actual city ID -->
                                            <input type="hidden" id="edit_city_id" name="city_id" value="">
                                            <!-- Visible search input -->
                                            <input type="text" class="form-control city-search-input"
                                                id="city_search_input" placeholder="Start typing to search cities..."
                                                autocomplete="off">
                                            <!-- Dropdown results -->
                                            <div class="city-dropdown" id="city_dropdown" style="display: none;">
                                                <div class="city-dropdown-content">
                                                    <div class="city-no-results" style="display: none;">
                                                        <div class="p-3 text-muted text-center">No cities found</div>
                                                    </div>
                                                </div>
                                            </div>
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
