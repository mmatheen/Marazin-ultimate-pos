{{-- Add/Edit modal row --}}
<div class="row">
    <div id="addAndEditSupplierModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="addAndEditSupplierModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addAndUpdateForm">
                        <input type="hidden" name="edit_id" id="edit_id">
                        <div class="row g-3">
                            <div class="col-md-4 col-sm-6">
                                <div class="mb-3">
                                    <label for="edit_prefix" class="form-label">Prefix<span class="login-danger">*</span></label>
                                    <select class="form-select" id="edit_prefix" name="prefix" required>
                                        <option selected disabled>Mr / Mrs / Miss</option>
                                        <option>Mr</option>
                                        <option>Mrs</option>
                                        <option>Ms</option>
                                        <option>Miss</option>
                                    </select>
                                    <span class="text-danger" id="prefix_error"></span>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <div class="mb-3">
                                    <label for="edit_first_name" class="form-label">First Name<span class="login-danger">*</span></label>
                                    <input class="form-control" id="edit_first_name" name="first_name" type="text" placeholder="First Name" required>
                                    <span class="text-danger" id="first_name_error"></span>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <div class="mb-3">
                                    <label for="edit_last_name" class="form-label">Last Name</label>
                                    <input class="form-control" id="edit_last_name" name="last_name" type="text" placeholder="Last Name">
                                    <span class="text-danger" id="last_name_error"></span>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <div class="mb-3">
                                    <div class="input-group">
                                        <span class="input-group-text" id="email-addon"><i class="fas fa-envelope"></i></span>
                                        <input type="email" class="form-control" id="edit_email" name="email" placeholder="Email" aria-describedby="email-addon">
                                        <span class="text-danger" id="email_error"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <div class="mb-3">
                                    <div class="input-group">
                                        <span class="input-group-text" id="mobile-addon"><i class="fas fa-mobile-alt"></i></span>
                                        <input type="text" class="form-control" id="edit_mobile_no" name="mobile_no" placeholder="Mobile" aria-describedby="mobile-addon">
                                        <span class="text-danger" id="mobile_no_error"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <div class="mb-3">
                                    <div class="input-group">
                                        <span class="input-group-text" id="address-addon"><i class="fas fa-address-book"></i></span>
                                        <input type="text" class="form-control" id="edit_address" name="address" placeholder="Address" aria-describedby="address-addon">
                                        <span class="text-danger" id="address_error"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <div class="mb-3">
                                    <div class="input-group">
                                        <span class="input-group-text" id="opening-balance-addon"><i class="fas fa-dollar-sign"></i></span>
                                        <input type="text" class="form-control" id="edit_opening_balance" name="opening_balance" placeholder="Opening Balance" aria-describedby="opening-balance-addon">
                                        <span class="text-danger" id="opening_balance_error"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" id="modalButton" class="btn btn-outline-primary">Save</button>
                            <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
