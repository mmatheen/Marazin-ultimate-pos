
  {{-- Add/Edit modal row --}}
  <div class="row">
    <div id="addAndEditUnitModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="text-center mt-2 mb-4">
                        <h5 id="modalTitle"></h5>
                    </div>
                    <form id="addAndUpdateForm">

                        <input type="hidden" name="edit_id" id="edit_id">

                        <div class="col-md-12">
                            <div class="mb-3">
                                <div class="form-group local-forms">
                                    <label>Name<span class="login-danger">*</span></label>
                                    <input id="edit_name"  name="name" class="form-control" type="text" placeholder="Name">
                                    <span class="text-danger" id="name_error"></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <div class="form-group local-forms">
                                    <label>Short name<span class="login-danger">*</span></label>
                                    <input id="edit_short_name" name="short_name" class="form-control" type="text" placeholder="Short name">
                                    <span class="text-danger" id="short_name_error"></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group local-forms">
                                <label>Allow decimal<span class="login-danger"></span></label>
                                <select id="edit_allow_decimal" name="allow_decimal" class="form-control form-select ">
                                    <option selected disabled>Please Select </option>
                                    <option value="yes">yes</option>
                                    <option value="no">no</option>
                                </select>
                                <span class="text-danger" id="allow_decimal_error"></span>
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
