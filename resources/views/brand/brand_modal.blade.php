  {{-- Add modal row --}}
  <div class="row">
    <div class="modal fade" id="addBrandModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Add brand</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addAndEditForm" method="POST" action="">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <div class="form-group local-forms">
                                        <label>Brand name<span class="login-danger">*</span></label>
                                        <input class="form-control" type="text" placeholder="Brand name">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div>
                                    <div class="form-group local-forms">
                                        <label>Short description<span class="login-danger"></span></label>
                                        <input class="form-control" type="text" placeholder="Short description">
                                        <span>Category code is same as HSN code</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12 ms-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="allowLoginCheckbox">
                                    <label class="form-check-label" for="allowLoginCheckbox">
                                        Add as sub taxonomy
                                    </label>
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
{{-- Edit modal row --}}
