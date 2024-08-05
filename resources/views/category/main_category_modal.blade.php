<!-- Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Category</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <form id="addAndEditForm" method="POST" action="">
                        <div class="col-md-12 mb-3 ms-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="allowLoginCheckbox"
                                    data-bs-target="#moreinformation1" data-bs-toggle="collapse" aria-expanded="false"
                                    aria-controls="collapseExample">
                                <label class="form-check-label" for="allowLoginCheckbox">
                                    Add Parent Category
                                </label>
                            </div>

                        </div>

                        <div class="collapse" id="moreinformation1">
                            <div class="col-sm-12">
                                <div class="card">
                                    <div class="card-body" style="background: rgb(209, 228, 243); border-radius: 5px">
                                        <div class="page-header">
                                          
                                                <div class="student-group-form">
                                                    <div class="row mt-4">
                                                        <div class="col-md-12">
                                                            <div>
                                                                <div class="form-group local-forms">
                                                                    <label  style="background: rgb(209, 228, 243); ">Parent Category Name<span class="login-danger">*</span></label>
                                                                    <input class="form-control" type="text"
                                                                        placeholder="Parent Category Name">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col">
                                                            <button type="button" class="btn btn-outline-primary me-3"
                                                                onclick="toggleLoginFields()">Save</button>
                                                            <button type="button" class="btn btn-outline-secondary"
                                                                data-bs-target="#moreinformation1" data-bs-toggle="collapse"
                                                                aria-expanded="false" aria-controls="collapseExample">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                        
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                        </div>

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
                <button type="button" class="btn btn-outline-primary">Save</button>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>

    </div>
    <script>
        function toggleLoginFields() {
            alert('hi')
            var checkBox = document.getElementById("allowLoginCheckbox");
            checkBox.checked = false;

        }
    </script>
