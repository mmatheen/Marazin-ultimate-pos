<div class="row">
    <div id="addAndEditRoleModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="text-center mt-2 mb-4">
                        <h5 id="modalTitle"></h5>
                    </div>
                    <form id="addAndUpdateForm">

                        <input type="hidden" name="edit_id" id="edit_id">

                        <div class="mb-3">
                            <div class="form-group local-forms">
                                <label>Role Name <span class="login-danger">*</span></label>
                                <input class="form-control" id="edit_name" name="name" type="text" placeholder="Enter Role Name">
                                <span class="text-danger" id="name_error"></span>
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