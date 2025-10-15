{{-- Add/Edit City modal row --}}
<div class="row">
    <div id="addAndEditCityModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="text-center mt-2 mb-4">
                        <h5 id="cityModalTitle">New City</h5>
                    </div>
                    <form id="cityAddAndUpdateForm">
                        <input type="hidden" name="edit_city_id" id="edit_city_id">

                        <div class="mb-3">
                            <div class="form-group local-forms">
                                <label>City Name <span class="login-danger">*</span></label>
                                <input class="form-control" id="edit_city_name" name="name" type="text"
                                    placeholder="City Name">
                                <span class="text-danger" id="city_name_error"></span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-group local-forms">
                                <label>Province <span class="login-danger">*</span></label>
                                <select class="form-control form-select" id="edit_city_province" name="province">
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
                                <span class="text-danger" id="city_province_error"></span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-group local-forms">
                                <label>District <span class="login-danger">*</span></label>
                                <select class="form-control form-select" id="edit_city_district" name="district"
                                    disabled>
                                    <option value="">Select District</option>
                                </select>
                                <span class="text-danger" id="city_district_error"></span>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="submit" id="cityModalButton" class="btn btn-outline-primary">Save</button>
                            <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
