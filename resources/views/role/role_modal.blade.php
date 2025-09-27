<div id="addAndEditRoleModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <div class="text-center mt-2 mb-4">
                    <h5 id="modalTitle">Add Role</h5>
                </div>
                <form id="addAndRoleUpdateForm">

                    <input type="hidden" name="edit_id" id="edit_id">

                    <div class="mb-3">
                        <label>Role Name <span class="text-danger">*</span></label>
                        <input class="form-control" id="edit_name" name="name" type="text"
                            placeholder="e.g. Sales Executive">
                        <span class="text-danger" id="name_error"></span>
                    </div>

                    <div class="mb-3">
                        <label>Role Type (System Key) <span class="text-danger">*</span></label>
                        <select name="key" id="edit_key" class="form-control" required>
                            <option value="">Select Role Type</option>
                            <option value="super_admin">Super Admin</option>
                            <option value="admin">Admin</option>
                            <option value="manager">Manager</option>
                            <option value="sales_rep">Sales Rep</option>
                            <option value="cashier">Cashier</option>
                        </select>
                        <span class="text-danger" id="key_error"></span>
                        <small class="text-muted">This defines what the role can do in the system.</small>
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
