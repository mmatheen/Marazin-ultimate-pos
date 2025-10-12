

<style>
     @media (max-width: 575.98px) {
        .select2Box{
        width: 100% !important;
        }
        }

        @media (min-width: 576px) {
            .select2Box{
            width: 100% !important;
    }
        }
</style>

<div id="addAndEditSubCategoryModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="addAndUpdateForm">
                <div class="modal-body" >
                    <div class="text-center mt-2 mb-4">
                        <h5 id="modalTitle"></h5>
                    </div>

                    <input type="hidden" name="edit_id" id="edit_id">

                    <div class="col-md-12">
                        <div class="form-group local-forms">
                            <label>main category<span class="login-danger">*</span></label>
                            <select id="edit_main_category_id_sub" name="main_category_id" class="form-control selectBox">
                                <option selected disabled>Please Select</option>
            
                            </select>
                            <span class="text-danger" id="main_category_id_error"></span>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="mb-3">
                            <div class="form-group local-forms">
                                <label>sub category<span class="login-danger">*</span></label>
                                <input id="edit_subCategoryname" name="subCategoryname" class="form-control" type="text" placeholder="sub category">
                                <span class="text-danger" id="subCategoryname_error"></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="mb-3">
                            <div class="form-group local-forms">
                                <label>category code<span class="login-danger"></span></label>
                                <input id="edit_subCategoryCode" name="subCategoryCode" class="form-control" type="text" placeholder="category code">
                                <span class="text-danger" id="subCategoryCode_error"></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="mb-3">
                            <div class="form-group local-forms">
                                <label>description<span class="login-danger"></span></label>
                                <textarea id="edit_description" name="description" class="form-control" type="text" placeholder="description"></textarea>
                                <span class="text-danger" id="description_error"></span>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" id="modalButton" class="btn btn-outline-primary">Save</button>
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

