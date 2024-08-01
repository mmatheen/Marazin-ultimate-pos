<!-- Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Modal title</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <form id="addAndEditForm" method="POST" action="">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <div class="form-group local-forms">
                                    <label>Category name<span class="login-danger">*</span></label>
                                    <input class="form-control" type="text" placeholder="Category name">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="mb-3">
                                <div class="form-group local-forms">
                                    <label>Category Code<span class="login-danger"></span></label>
                                    <input class="form-control" type="text" placeholder="Category Code">
                                    <span>Category code is same as HSN code</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="mb-3">
                                <div class="form-group local-forms">
                                    <label>Description <span class="login-danger"></span></label>
                                    <textarea class="form-control" type="text" placeholder="Description"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="allowLoginCheckbox" onclick="toggleLoginFields()">
                                <label class="form-check-label" for="allowLoginCheckbox">
                                    Add as sub taxonomy
                                </label>
                            </div>
                        </div>
                        <div class="row mt-4 hidden">
                            <div class="col-md-12 hidingclass">
                                <div class="form-group local-forms days">
                                    <label>Select parent category<span class="login-danger"></span></label>
                                    <select class="form-control form-select select">
                                        <option selected disabled>Select parent category</option>
                                        <option>Biscuits</option>
                                        <option>Chocolate</option>
                                        <option>Electronics</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-primary">Save changes</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
