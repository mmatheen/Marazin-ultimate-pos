{{-- Add/Edit modal row --}}
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
                                    <div class="form-group local-forms">
                                        <label>Prefix<span class="login-danger">*</span></label>
                                        <select class="form-control form-select" id="edit_prefix" name="prefix">
                                            <option selected disabled>Mr / Mrs / Miss</option>
                                            <option>Mr</option>
                                            <option>Mrs</option>
                                            <option>Ms</option>
                                            <option>Miss</option>
                                        </select>
                                        <span class="text-danger" id="prefix_error"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <div class="form-group local-forms">
                                        <label>First Name<span class="login-danger">*</span></label>
                                        <input class="form-control" id="edit_first_name" name="first_name" type="text" placeholder="First Name">
                                        <span class="text-danger" id="first_name_error"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <div class="form-group local-forms">
                                        <label>Last Name<span class="login-danger"></span></label>
                                        <input class="form-control" id="edit_last_name" name="last_name" type="text" placeholder="Last Name">
                                        <span class="text-danger" id="last_name_error"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <div class="input-group mb-3">
                                        <span class="input-group-text" id="basic-addon1"><i class="fas fa-mobile-alt"></i></span>
                                        <input type="text" class="form-control" id="edit_email" name="email" placeholder="Email" aria-label="Example text with button addon" aria-describedby="button-addon1">
                                        <span class="text-danger" id="email_error"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <div class="input-group mb-3">
                                        <span class="input-group-text" id="basic-addon1"><i class="fas fa-envelope"></i></span>
                                        <input type="text" class="form-control" id="edit_mobile_no" name="mobile_no" placeholder="Mobile" aria-label="Example text with button addon" aria-describedby="button-addon1">
                                        <span class="text-danger" id="mobile_no_error"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <div class="input-group mb-3">
                                        <span class="input-group-text" id="basic-addon1"><i class="fas fa-address-book"></i></span>
                                        <input type="text" class="form-control" id="edit_contact_id" name="contact_id" placeholder="Contact ID" aria-label="Example text with button addon" aria-describedby="button-addon1">
                                        <span class="text-danger" id="contact_id_error"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3 mt-3">
                                    <div class="input-group local-forms">
                                        <span class="input-group-text" id="basic-addon1"><i class="fas fa-user"></i></span>
                                        <select class="form-control form-select" id="edit_contact_type" name="contact_type" aria-label="Example text with button addon" aria-describedby="button-addon1">
                                            <option selected disabled>Contact type</option>
                                            <option>Suppliers</option>
                                            <option>Customers</option>
                                            <option>Both (Supplier & Customer)</option>
                                        </select>
                                        <span class="text-danger" id="contact_type_error"></span>
                                    </div>
                                </div>

                            </div>
                            <div class="col-md-4">
                                <div class="mb-3 mt-3">
                                    <div class="form-group local-forms calendar-icon">
                                        <label>Date <span class="login-danger">*</span></label>
                                        <input class="form-control datetimepicker" id="edit_date" name="date" type="text" placeholder="DD-MM-YYYY">
                                        <span class="text-danger" id="date_error"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3 mt-3">
                                    <div class="input-group local-forms">
                                        <span class="input-group-text" id="basic-addon1"><i class="fas fa-user"></i></span>
                                        <select class="form-control form-select" id="edit_assign_to" name="assign_to" aria-label="Example text with button addon" aria-describedby="basic-addon1">
                                            <option selected disabled>Assigned to</option>
                                            <option>Mr SuperUser</option>
                                            <option>Mr Ahshan</option>
                                            <option>Mr Afshan</option>
                                        </select>
                                        <span class="text-danger" id="assign_to_error"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <div class="input-group mb-3">
                                        <span class="input-group-text" id="basic-addon1"><i class="fas fa-address-book"></i></span>
                                        <input type="text" class="form-control" id="edit_opening_balance" name="opening_balance" placeholder="Opening Balance" aria-label="Example text with button addon" aria-describedby="button-addon1">
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
