
<div id="addAndEditMainCategoryModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="addAndUpdateForm">
                <div class="modal-body">
                    <div class="text-center mt-2 mb-4">
                        <h5 id="modalTitle"></h5>
                    </div>

                    <input type="hidden" name="edit_id" id="edit_id">
                    <form id="addAndUpdateForm">
                        <div class="col-md-12 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="allowLoginCheckbox" data-bs-target="#moreinformation1" data-bs-toggle="collapse" aria-expanded="false" aria-controls="collapseExample">
                                <label class="form-check-label" for="allowLoginCheckbox">
                                    Add Main Category
                                </label>
                            </div>
                        </div>

                        <div class="collapse" id="moreinformation1">
                            <div class="col-md-12">
                                <div class="form-group local-forms">
                                    <label>Parent Category Name<span class="login-danger">*</span></label>
                                    <input class="form-control" type="text" placeholder="Parent Category Name">
                                </div>
                            </div>
                            <div class="col-sm-12 mb-5">
                                <div class="card-body" style="background: rgba(223, 221, 221, 0.651); border-radius: 5px">
                                    <div class="col">
                                        <button type="button" class="btn btn-outline-primary me-3" onclick="toggleLoginFields()">Save</button>
                                        <button type="button" class="btn btn-outline-secondary" data-bs-target="#moreinformation1" data-bs-toggle="collapse" aria-expanded="false" aria-controls="collapseExample">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    <div class="col-md-12">
                        <div class="form-group local-forms">
                            <label>main category<span class="login-danger">*</span></label>
                            <select id="edit_main_category_id" name="main_category_id" class="form-control select2Box form-select select">
                                <option selected disabled>Please Select </option>
                                @foreach($MainCategories as $MainCategory)
                                <option value="{{ $MainCategory->id }}">{{ $MainCategory->mainCategoryName }}</option>
                                @endforeach
                            </select>
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
                                <label>category code<span class="login-danger">*</span></label>
                                <input id="edit_subCategoryCode" name="subCategoryCode" class="form-control" type="text" placeholder="category code">
                                <span class="text-danger" id="subCategoryCode_error"></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="mb-3">
                            <div class="form-group local-forms">
                                <label>description<span class="login-danger">*</span></label>
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

