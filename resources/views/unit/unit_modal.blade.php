<div class="row">
    <div class="modal fade" id="addUnitModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Add Unit</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addAndEditForm" method="POST" action="">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <div class="form-group local-forms">
                                        <label>Name<span class="login-danger">*</span></label>
                                        <input class="form-control" type="text" placeholder="Awesome shop">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <div class="form-group local-forms">
                                        <label>Short name<span class="login-danger">*</span></label>
                                        <input class="form-control" type="text" placeholder="Awesome shop">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group local-forms">
                                    <label>Allow decimal<span class="login-danger"></span></label>
                                    <select class="form-control form-select select">
                                        <option selected disabled>Please Select </option>
                                        <option>Yes</option>
                                        <option>No</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary">Save changes</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
